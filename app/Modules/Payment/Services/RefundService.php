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
    ) {}

    /**
     * Refund a paid order — full (default) or partial. A full refund marks the
     * order refunded and restocks its items; a partial refund only returns the
     * money, leaving the order paid and stock untouched.
     */
    public function refund(Order $order, Admin $admin, ?Money $amount = null, ?string $reason = null): Refund
    {
        if ($order->payment_status !== PaymentStatus::Paid) {
            throw new RuntimeException('Only paid orders can be refunded.');
        }

        $refundAmount = $amount ?? $order->total;

        if ($order->total->lessThan($refundAmount)) {
            throw new RuntimeException('A refund cannot exceed the order total.');
        }

        $isFull = ! $refundAmount->lessThan($order->total);
        $payment = $order->payment;

        // Online (Paystack) orders refund through the gateway; manual orders
        // (bank transfer / POD) are recorded as an out-of-band refund.
        if ($payment) {
            $result = $this->gateway->refund($payment->reference, $refundAmount->kobo);
            $success = (bool) $result['success'];
            $raw = $result['raw'];
        } else {
            $success = true;
            $raw = ['manual' => true];
        }

        $refund = $order->refunds()->create([
            'payment_id' => $payment?->id,
            'admin_id' => $admin->id,
            'amount' => $refundAmount,
            'reason' => $reason,
            'status' => $success ? 'succeeded' : 'failed',
            'provider_reference' => data_get($raw, 'data.id'),
            'payload' => $raw,
        ]);

        if (! $success) {
            Log::warning('Refund failed', ['order_number' => $order->order_number, 'admin_id' => $admin->id]);

            return $refund;
        }

        DB::transaction(function () use ($order, $isFull): void {
            if (! $isFull) {
                return; // partial refund: money only, order stays paid
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
}
