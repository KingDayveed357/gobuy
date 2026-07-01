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
        if ($order->payment_status !== PaymentStatus::Paid && $order->payment_status !== PaymentStatus::PartiallyRefunded) {
            throw new RuntimeException('Only paid or partially refunded orders can be refunded.');
        }

        $refundAmount = $amount ?? $order->total;

        // Phase 1: Record intent atomically with a row lock
        $refund = DB::transaction(function () use ($order, $refundAmount, $admin, $reason, &$isFull, &$cashRefund, &$creditRefund, &$payment) {
            $fresh = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($refundAmount->kobo > $fresh->refundableRemaining()->kobo) {
                throw new RuntimeException('A refund cannot exceed the remaining refundable amount.');
            }

            $isFull = ! $refundAmount->lessThan($fresh->total);
            $payment = $fresh->payment;

            // Split across tenders: refund the cash collected first, then return any
            // store credit that was applied.
            $cashTender = $fresh->amountDue();                  // total − store credit
            $cashRefund = $refundAmount->min($cashTender);
            $creditRefund = $refundAmount->minus($cashRefund);

            return $fresh->refunds()->create([
                'payment_id' => $payment?->id,
                'admin_id' => $admin->id,
                'amount' => $cashRefund,
                'reason' => $reason,
                'status' => 'pending',
                'payload' => [
                    'completion' => 'order',
                    'total_amount_kobo' => $refundAmount->kobo,
                    'credit_amount_kobo' => $creditRefund->kobo,
                    'refund_type' => $isFull ? 'full' : 'partial',
                ],
            ]);
        });

        // Phase 2: Call gateway OUTSIDE any lock
        if ($cashRefund->isPositive() && $payment) {
            $result = $this->gateway->refund($payment->reference, $cashRefund->kobo);
            $raw = $result['raw'];
            $providerReference = data_get($raw, 'data.id');

            // A declined gateway refund is terminal — leave the order untouched.
            if (! (bool) $result['success']) {
                $refund->update([
                    'status' => 'failed',
                    'provider_reference' => $providerReference,
                    'payload' => array_merge($refund->payload ?? [], $raw),
                ]);

                Log::warning('Gateway refund declined', [
                    'order_number' => $order->order_number,
                    'amount_kobo' => $cashRefund->kobo,
                ]);

                return $refund;
            }

            // Accepted by the gateway → finalise now. The refund.processed webhook
            // is an idempotent reconciliation: a no-op once succeeded, but it will
            // finalise this refund if a gateway timeout ever leaves it unconfirmed.
            $refund->update([
                'status' => 'succeeded',
                'provider_reference' => $providerReference,
                'payload' => array_merge($refund->payload ?? [], $raw),
            ]);

            $this->completeRefund($order, $isFull, $refundAmount, $creditRefund, $admin, $refund);

            Log::info('Refund succeeded via gateway', [
                'order_number' => $order->order_number,
                'amount_kobo' => $refundAmount->kobo,
            ]);

            return $refund;
        }

        // Manual or Store Credit Only path
        $raw = $cashRefund->isPositive() ? ['manual' => true] : ['store_credit_only' => true];

        $refund->update([
            'status' => 'succeeded',
            'payload' => array_merge($refund->payload ?? [], $raw),
        ]);

        $this->completeRefund($order, $isFull, $refundAmount, $creditRefund, $admin, $refund);

        Log::info('Refund succeeded (manual/credit)', [
            'order_number' => $order->order_number,
            'amount_kobo' => $refundAmount->kobo,
            'full' => $isFull,
            'admin_id' => $admin->id,
        ]);

        return $refund;
    }

    public function completeRefund(Order $order, bool $isFull, Money $refundAmount, Money $creditRefund, Admin $admin, Refund $refund): void
    {
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
                $order->update(['payment_status' => PaymentStatus::PartiallyRefunded]);

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

        // Phase 1: Record intent atomically with a row lock
        $refund = DB::transaction(function () use ($order, $amount, $admin, $reason, &$payment) {
            $fresh = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($amount->kobo > $fresh->refundableRemaining()->kobo) {
                throw new RuntimeException('A refund cannot exceed the remaining refundable amount.');
            }

            $payment = $fresh->payment;

            return $fresh->refunds()->create([
                'payment_id' => $payment?->id,
                'admin_id' => $admin->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'pending',
                'payload' => ['completion' => 'return'],
            ]);
        });

        // Phase 2: Call gateway OUTSIDE any lock
        if ($payment) {
            $result = $this->gateway->refund($payment->reference, $amount->kobo);
            $raw = $result['raw'];
            $providerReference = data_get($raw, 'data.id');

            // A declined gateway refund is terminal; the caller (settlement) aborts.
            if (! (bool) $result['success']) {
                $refund->update([
                    'status' => 'failed',
                    'provider_reference' => $providerReference,
                    'payload' => array_merge($refund->payload ?? [], $raw),
                ]);

                return $refund;
            }

            $refund->update([
                'status' => 'succeeded',
                'provider_reference' => $providerReference,
                'payload' => array_merge($refund->payload ?? [], $raw),
            ]);
            $this->applyReturnRefundLedger($order, $amount);

            return $refund;
        }

        // Manual offline reversal (no online payment) — immediately succeeded.
        $refund->update([
            'status' => 'succeeded',
            'payload' => array_merge($refund->payload ?? [], ['manual' => true]),
        ]);
        $this->applyReturnRefundLedger($order, $amount);

        return $refund;
    }

    /**
     * Bump the order's shared over-refund ledger for a Returns-module refund and
     * reflect partial/full refund on the payment status. The Returns settlement
     * owns restock + return closure; this method only touches the money ledger.
     */
    private function applyReturnRefundLedger(Order $order, Money $amount): void
    {
        Order::whereKey($order->id)->update(['refunded_total' => DB::raw('refunded_total + '.$amount->kobo)]);
        $order->update(['payment_status' => PaymentStatus::PartiallyRefunded]);
    }

    /**
     * Idempotent `refund.processed` webhook handler. In the happy path the refund
     * is already 'succeeded' (finalised synchronously when the gateway accepted)
     * and this is a no-op. Its real job is reconciliation: finalise a refund that
     * a gateway timeout left 'pending'/'processing' so money is never stranded.
     */
    public function markConfirmed(string $providerReference, array $payload = []): void
    {
        $refund = Refund::with(['order', 'admin'])->firstWhere('provider_reference', $providerReference);

        if (! $refund || in_array($refund->status, ['succeeded', 'failed'], true)) {
            return; // unknown or already terminal
        }

        $refund->update([
            'status' => 'succeeded',
            'payload' => array_merge($refund->payload ?? [], $payload),
        ]);

        if (! $refund->order) {
            return;
        }

        if (data_get($refund->payload, 'completion') === 'return') {
            // Returns settlement already restocked/closed — apply the money ledger only.
            $this->applyReturnRefundLedger($refund->order, $refund->amount);
        } elseif ($refund->admin) {
            $isFull = data_get($refund->payload, 'refund_type') === 'full';
            $refundAmount = Money::fromKobo((int) data_get($refund->payload, 'total_amount_kobo', 0));
            $creditRefund = Money::fromKobo((int) data_get($refund->payload, 'credit_amount_kobo', 0));
            $this->completeRefund($refund->order, $isFull, $refundAmount, $creditRefund, $refund->admin, $refund);
        }

        Log::info('Refund confirmed via webhook', [
            'provider_reference' => $providerReference,
            'refund_id' => $refund->id,
        ]);
    }

    /**
     * `refund.failed` webhook handler. Only acts on a still-pending refund — a
     * refund already finalised as 'succeeded' is never silently un-settled (that
     * would need an explicit reversal); such a case is logged for intervention.
     */
    public function markFailed(string $providerReference, array $payload = []): void
    {
        $refund = Refund::firstWhere('provider_reference', $providerReference);

        if (! $refund || in_array($refund->status, ['failed', 'succeeded'], true)) {
            if ($refund && $refund->status === 'succeeded') {
                Log::critical('refund.failed received for an already-succeeded refund', [
                    'provider_reference' => $providerReference,
                    'refund_id' => $refund->id,
                ]);
            }

            return;
        }

        $refund->update([
            'status' => 'failed',
            'payload' => array_merge($refund->payload ?? [], $payload),
        ]);

        Log::warning('Refund failed via webhook', [
            'provider_reference' => $providerReference,
            'refund_id' => $refund->id,
        ]);
    }
}
