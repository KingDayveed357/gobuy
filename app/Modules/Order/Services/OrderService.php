<?php

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Order\DTOs\CheckoutData;
use App\Modules\Order\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create an order (and its snapshotted items) from a priced cart summary.
     *
     * @param  array{lines: array<int, array{item: mixed, price: \App\Modules\Pricing\ValueObjects\ResolvedPrice, lineTotal: float}>, subtotal: float, count: int}  $summary
     */
    public function createFromCart(CheckoutData $data, array $summary, ?User $user): Order
    {
        $deliveryFee = (float) config('gobuy.delivery_fee');
        $subtotal = $summary['subtotal'];

        return DB::transaction(function () use ($data, $summary, $user, $subtotal, $deliveryFee): Order {
            $order = Order::create([
                ...$data->toOrderAttributes(),
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user?->id,
                'customer_type' => $user?->customer_type ?? User::TYPE_RETAIL,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $subtotal + $deliveryFee,
                'placed_at' => now(),
            ]);

            foreach ($summary['lines'] as $line) {
                $product = $line['item']->product;

                $order->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $line['price']->unitPrice,
                    'quantity' => $line['item']->quantity,
                    'line_total' => $line['lineTotal'],
                ]);
            }

            return $order;
        });
    }

    public function generateOrderNumber(): string
    {
        do {
            $number = 'GB-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
