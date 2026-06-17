<?php

namespace App\Modules\Order\Actions;

use App\Modules\Cart\Services\CartService;
use App\Modules\Order\DTOs\CheckoutData;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Order\Services\OrderStatusService;
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
    ) {}

    public function execute(CheckoutData $data): Order
    {
        $summary = $this->cart->summary();

        if (empty($summary['lines'])) {
            throw new RuntimeException('Cannot place an order with an empty cart.');
        }

        $this->assertStockAvailable($summary['lines']);

        $order = $this->orders->createFromCart($data, $summary, Auth::user());
        $this->status->recordInitial($order);

        Log::info('Order placed', [
            'order_number' => $order->order_number,
            'total' => $order->total,
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
            $product = $line['item']->product;

            if ($product->stock < $line['item']->quantity) {
                throw new RuntimeException("Insufficient stock for {$product->name}.");
            }
        }
    }
}
