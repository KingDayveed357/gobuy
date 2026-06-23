<?php

namespace App\Modules\Returns\Services;

use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Carbon\CarbonInterface;

/**
 * Decides whether an order — and each of its lines — may be returned, applying
 * the layered policy: order status, delivery window (per-product override over
 * the config default), product opt-out, excluded categories, and how many units
 * of a line remain un-returned.
 */
class ReturnEligibilityService
{
    /**
     * Eligibility for a whole order, partitioned into returnable and blocked
     * lines. The order is eligible if at least one line can be returned.
     *
     * @return array{eligible: bool, reason: ?string, window_expires_at: ?CarbonInterface, items: array<int, OrderItem>, blocked: array<int, array{item: OrderItem, reason: string}>}
     */
    public function forOrder(Order $order, ?User $user = null): array
    {
        if ($user && $order->user_id !== $user->id) {
            return $this->blocked('This order does not belong to you.');
        }

        if (! in_array($order->status->value, config('gobuy.returns.eligible_order_statuses'), true)) {
            return $this->blocked('This order is not eligible for returns yet.');
        }

        if ($order->delivered_at === null) {
            return $this->blocked('This order has not been delivered yet.');
        }

        $order->loadMissing('items.variant.product.category');

        $items = [];
        $blocked = [];

        foreach ($order->items as $item) {
            $reason = $this->itemBlockReason($item, $order);
            if ($reason === null) {
                $items[] = $item;
            } else {
                $blocked[] = ['item' => $item, 'reason' => $reason];
            }
        }

        return [
            'eligible' => $items !== [],
            'reason' => $items !== [] ? null : 'No items on this order can be returned.',
            'window_expires_at' => $this->orderDeadline($order),
            'items' => $items,
            'blocked' => $blocked,
        ];
    }

    /**
     * Why a single line cannot be returned, or null if it can.
     */
    public function itemBlockReason(OrderItem $item, ?Order $order = null): ?string
    {
        $order ??= $item->order;

        if ($item->returnableQuantity() < 1) {
            return 'All units of this item have already been returned.';
        }

        $product = $item->variant?->product;
        if ($product === null) {
            return 'This product is no longer available to return.';
        }

        if (! $product->is_returnable) {
            return 'This item is marked non-returnable.';
        }

        if (in_array($product->category?->slug, config('gobuy.returns.excluded_category_slugs'), true)) {
            return 'Items in this category cannot be returned.';
        }

        $deadline = $this->itemDeadline($item, $order);
        if ($deadline !== null && now()->greaterThan($deadline)) {
            return 'The return window for this item has closed.';
        }

        return null;
    }

    /**
     * Per-line deadline: delivery date + the product's window override, else the
     * configured default window.
     */
    public function itemDeadline(OrderItem $item, ?Order $order = null): ?CarbonInterface
    {
        $order ??= $item->order;
        if ($order->delivered_at === null) {
            return null;
        }

        $days = $item->variant?->product?->return_window_days ?? (int) config('gobuy.returns.window_days');

        return $order->delivered_at->copy()->addDays($days);
    }

    private function orderDeadline(Order $order): ?CarbonInterface
    {
        if ($order->delivered_at === null) {
            return null;
        }

        return $order->delivered_at->copy()->addDays((int) config('gobuy.returns.window_days'));
    }

    /**
     * @return array{eligible: false, reason: string, window_expires_at: null, items: array<int, OrderItem>, blocked: array<int, array{item: OrderItem, reason: string}>}
     */
    private function blocked(string $reason): array
    {
        return ['eligible' => false, 'reason' => $reason, 'window_expires_at' => null, 'items' => [], 'blocked' => []];
    }
}
