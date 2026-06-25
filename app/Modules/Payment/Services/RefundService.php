<?php

namespace App\Modules\Payment\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStatusService;
use App\Modules\Payment\Contracts\PaymentGateway;
use App\Modules\Payment\Models\Refund;
use App\Modules\Returns\Services\StoreCreditService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RefundService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly OrderStatusService $status,
        private readonly CatalogService $catalog,
        private readonly StoreCreditService $storeCredit,
    ) {}

    /**
     * Refund a paid order — full (default) or partial. A full refund marks the
     * order refunded and restocks its items; a partial refund only returns the
     * money, leaving the order paid and stock untouched.
     *
     * Refunds are split back across the original tenders: the cash portion (what
     * the gateway/POD/bank actually collected) is reversed through the gateway,
     * and any store credit that was applied is returned to the wallet — so the
     * gateway is never refunded more than it took.
     */
    public function refund(Order $order, Admin $admin, ?Money $amount = null, ?string $reason = null): Refund
    {
        if ($order->payment_status !== PaymentStatus::Paid) {
            throw new RuntimeException('Only paid orders can be refunded.');
        }

        $refundAmount = $amount ?? $order->total;

        if ($refundAmount->kobo > $order->refundableRemaining()->kobo) {
            throw new RuntimeException('A refund cannot exceed the remaining refundable amount.');
        }

        $isFull = ! $refundAmount->lessThan($order->total);
        $payment = $order->payment;

        // Split across tenders: refund the cash collected first, then return any
        // store credit that was applied.
        $cashTender = $order->amountDue();                  // total − store credit
        $cashRefund = $refundAmount->min($cashTender);
        $creditRefund = $refundAmount->minus($cashRefund);

        // Online (Paystack) orders refund through the gateway; manual orders
        // (bank transfer / POD) are recorded as an out-of-band refund. A
        // credit-only refund touches no gateway.
        if ($cashRefund->isPositive() && $payment) {
            $result = $this->gateway->refund($payment->reference, $cashRefund->kobo);
            $success = (bool) $result['success'];
            $raw = $result['raw'];
        } else {
            $success = true;
            $raw = $cashRefund->isPositive() ? ['manual' => true] : ['store_credit_only' => true];
        }

        $refund = $order->refunds()->create([
            'payment_id' => $payment?->id,
            'admin_id' => $admin->id,
            'amount' => $cashRefund,
            'reason' => $reason,
            'status' => $success ? 'succeeded' : 'failed',
            'provider_reference' => data_get($raw, 'data.id'),
            'payload' => array_merge($raw, [
                'total_amount_kobo' => $refundAmount->kobo,
                'credit_amount_kobo' => $creditRefund->kobo,
                'refund_type' => $isFull ? 'full' : 'partial',
            ]),
        ]);

        if (! $success) {
            Log::warning('Refund failed', ['order_number' => $order->order_number, 'admin_id' => $admin->id]);

            return $refund;
        }

        DB::transaction(function () use ($order, $isFull, $refundAmount, $creditRefund, $admin, $refund): void {
            // Shared over-refund ledger — also read by the Returns module.
            // Atomic, cast-free (Money cast forbids ->increment()).
            Order::whereKey($order->id)->update(['refunded_total' => DB::raw('refunded_total + '.$refundAmount->kobo)]);

            // Return the store-credit portion to the customer's wallet.
            if ($creditRefund->isPositive() && $order->user) {
                $this->storeCredit->issue(
                    $order->user, $creditRefund, $order,
                    "refund-credit:{$refund->id}", "Store credit returned for order {$order->order_number}", $admin,
                );
            }

            if (! $isFull) {
                return; // partial refund: order stays paid
            }

            $order->update(['payment_status' => PaymentStatus::Refunded]);
            $this->status->transitionTo($order, OrderStatus::Refunded, 'Refund issued');

            foreach ($order->items as $item) {
                $variant = $item->product_variant_id ? ProductVariant::find($item->product_variant_id) : null;

                if ($variant) {
                    $this->catalog->restock($variant, $item->quantity);
                }
            }
        });

        Log::info('Refund succeeded', [
            'order_number' => $order->order_number,
            'amount_kobo' => $refundAmount->kobo,
            'full' => $isFull,
            'admin_id' => $admin->id,
        ]);

        return $refund;
    }

    /**
     * Money-only refund for the Returns module: pushes the money back to the
     * original payment method (gateway, or a recorded manual reversal) and bumps
     * the shared over-refund ledger — but does NOT restock or change the order
     * status. The Returns settlement owns those decisions per inspected item.
     *
     * Requires a gateway/manual payment to exist; POD orders that never paid
     * online must be settled to store credit instead (the caller decides).
     */
    public function refundForReturn(Order $order, Admin $admin, Money $amount, ?string $reason = null): Refund
    {
        if (! $amount->isPositive()) {
            throw new RuntimeException('Refund amount must be positive.');
        }

        $payment = $order->payment;
        if ($payment) {
            $result = $this->gateway->refund($payment->reference, $amount->kobo);
            $success = (bool) $result['success'];
            $raw = $result['raw'];
        } else {
            $success = true;       // manual/offline reversal recorded out of band
            $raw = ['manual' => true];
        }

        $refund = $order->refunds()->create([
            'payment_id' => $payment?->id,
            'admin_id' => $admin->id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => $success ? 'succeeded' : 'failed',
            'provider_reference' => data_get($raw, 'data.id'),
            'payload' => $raw,
        ]);

        if ($success) {
            Order::whereKey($order->id)->update(['refunded_total' => DB::raw('refunded_total + '.$amount->kobo)]);
        } else {
            Log::warning('Return refund failed', ['order_number' => $order->order_number, 'admin_id' => $admin->id]);
        }

        return $refund;
    }
}
