<?php

namespace App\Modules\Payment\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStatusService;
use App\Modules\Payment\Contracts\PaymentGateway;
use App\Modules\Payment\Models\Refund;
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
     * Refund a paid order (full refund only in this phase) and restock items.
     */
    public function refund(Order $order, Admin $admin, ?string $reason = null): Refund
    {
        if ($order->payment_status !== PaymentStatus::Paid) {
            throw new RuntimeException('Only paid orders can be refunded.');
        }

        $payment = $order->payment;

        if (! $payment) {
            throw new RuntimeException('No payment found for this order.');
        }

        $result = $this->gateway->refund($payment->reference, (float) $order->total);

        $refund = $order->refunds()->create([
            'payment_id' => $payment->id,
            'admin_id' => $admin->id,
            'amount' => $order->total,
            'reason' => $reason,
            'status' => $result['success'] ? 'succeeded' : 'failed',
            'provider_reference' => data_get($result['raw'], 'data.id'),
            'payload' => $result['raw'],
        ]);

        if (! $result['success']) {
            Log::warning('Refund failed', ['order_number' => $order->order_number, 'admin_id' => $admin->id]);

            return $refund;
        }

        DB::transaction(function () use ($order): void {
            $order->update(['payment_status' => PaymentStatus::Refunded]);
            $this->status->transitionTo($order, OrderStatus::Refunded, 'Refund issued');

            foreach ($order->items as $item) {
                $product = $item->product_id ? Product::find($item->product_id) : null;

                if ($product) {
                    $this->catalog->restock($product, $item->quantity);
                }
            }
        });

        Log::info('Refund succeeded', [
            'order_number' => $order->order_number,
            'amount' => $order->total,
            'admin_id' => $admin->id,
        ]);

        return $refund;
    }
}
