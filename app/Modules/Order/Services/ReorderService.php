<?php

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Models\Order;
use App\Modules\Pricing\Services\PricingEngine;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Resolves a past order into a re-orderable cart preview, surfacing exactly what
 * changed since the original purchase — discontinued items, out-of-stock or
 * low-stock lines, and price moves — so the customer is never surprised at the
 * cart. The actual add-to-cart still goes through the cart's reservation path.
 */
class ReorderService
{
    public function __construct(private readonly PricingEngine $pricing) {}

    public const OK = 'ok';

    public const PARTIAL = 'partial';            // some stock, less than wanted

    public const OUT_OF_STOCK = 'out_of_stock';

    public const UNAVAILABLE = 'unavailable';    // product/variant gone or inactive

    /**
     * @return array{lines: Collection<int, array<string, mixed>>, addable: int, has_changes: bool}
     */
    public function preview(Order $order, ?User $user = null): array
    {
        $order->loadMissing('items');

        $lines = $order->items->map(function ($item) use ($user) {
            $variant = $item->product_variant_id
                ? ProductVariant::with('product')->find($item->product_variant_id)
                : null;

            $product = $variant?->product;
            $wanted = (int) $item->quantity;

            if ($variant === null || $product === null || $product->status !== 'active') {
                return $this->line($item, null, $wanted, 0, self::UNAVAILABLE, false, $item->unit_price, null);
            }

            $available = min($wanted, max(0, (int) $variant->stock));
            $status = match (true) {
                $available <= 0 => self::OUT_OF_STOCK,
                $available < $wanted => self::PARTIAL,
                default => self::OK,
            };

            $current = $this->pricing->priceForVariant($variant, $user, max(1, $available))->unitPrice;
            $priceChanged = ! $current->equals($item->unit_price);

            return $this->line($item, $variant, $wanted, $available, $status, $priceChanged, $item->unit_price, $current);
        });

        return [
            'lines' => $lines,
            'addable' => $lines->where('addable', true)->count(),
            'has_changes' => $lines->contains(fn ($l) => $l['status'] !== self::OK || $l['price_changed']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function line($item, ?ProductVariant $variant, int $wanted, int $available, string $status, bool $priceChanged, Money $paid, ?Money $current): array
    {
        return [
            'order_item' => $item,
            'variant' => $variant,
            'name' => $item->name,
            'image' => $variant?->product?->imageUrl(),
            'wanted' => $wanted,
            'available' => $available,
            'status' => $status,
            'price_changed' => $priceChanged,
            'paid' => $paid,
            'current' => $current,
            'addable' => $available > 0,
        ];
    }
}
