<?php

namespace App\Modules\Order\Actions;

use App\Modules\Cart\Services\CartService;
use App\Modules\Logistics\Services\DeliveryFeeService;
use App\Modules\Logistics\Services\ShipmentService;
use App\Modules\Order\DTOs\CheckoutData;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Order\Services\OrderStatusService;
use App\Modules\Pricing\Services\CouponService;
use App\Modules\Returns\Services\StoreCreditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Turn the current cart into a pending order. This is the "complex flow"
 * the architecture reserves Actions for: Controller -> Action -> Services.
 *
 * Stock is NOT decremented here — that happens on confirmed payment.
 */
class PlaceOrderAction
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
        private readonly OrderStatusService $status,
        private readonly DeliveryFeeService $deliveryFees,
        private readonly ShipmentService $shipments,
        private readonly CouponService $coupons,
        private readonly StoreCreditService $storeCredit,
    ) {}

    public function execute(CheckoutData $data): Order
    {
        $summary = $this->cart->summary();

        if (empty($summary['lines'])) {
            throw new RuntimeException('Cannot place an order with an empty cart.');
        }

        $this->assertStockAvailable($summary['lines']);

        // Authoritative delivery fee (never trust the client) from zone + weight.
        $weight = (int) ($summary['weight'] ?? 0);
        $quote = $this->deliveryFees->quote($data->deliveryMethod, $data->state, $weight, $summary['subtotal']);

        // Re-validate any applied coupon server-side at the moment of placement.
        $user = Auth::user();
        $coupon = $this->coupons->resolveForCart($summary, $user);

        $order = $this->orders->createFromCart(
            $data, $summary, $user, $quote['fee'],
            $coupon['coupon'] ?? null, $coupon['discount'] ?? null,
        );

        if ($coupon) {
            $this->coupons->redeem($coupon['coupon'], $user, $order, $coupon['discount']);
            session()->forget(CouponService::SESSION_KEY);
        }

        // Tender store credit against the order (spent later, at acceptance).
        if ($user && session('checkout.apply_credit')) {
            $applied = $this->storeCredit->redeemableFor($user, $order->total);
            if ($applied->isPositive()) {
                $order->update(['store_credit_applied' => $applied]);
            }
        }

        $this->shipments->createForOrder($order, $data, $weight, $quote['zone']);
        $this->status->recordInitial($order);

        Log::info('Order placed', [
            'order_number' => $order->order_number,
            'total_kobo' => $order->total->kobo,
            'delivery_method' => $data->deliveryMethod,
            'user_id' => $order->user_id,
        ]);

        return $order;
    }

    /**
     * @param  array<int, array{item: mixed, price: mixed, lineTotal: float}>  $lines
     */
    private function assertStockAvailable(array $lines): void
    {
        foreach ($lines as $line) {
            $variant = $line['item']->variant;

            if (! $variant || $variant->stock < $line['item']->quantity) {
                $name = $variant?->product?->name ?? 'an item';
                throw new RuntimeException("Insufficient stock for {$name}.");
            }
        }
    }
}
