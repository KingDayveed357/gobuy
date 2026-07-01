<?php

namespace App\Modules\Review\Services;

use App\Admin\Models\Admin;
use App\Admin\Notifications\AdminAlertNotification;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Review\Models\Review;
use Illuminate\Support\Facades\Notification;

/**
 * Reviews are tied to verified purchases: a customer may review a product only
 * after an order containing it has been delivered. Aggregate ratings are cached
 * on the product and recomputed whenever an approved review set changes.
 */
class ReviewService
{
    /**
     * The customer's delivered/completed order that contains this product, if any.
     */
    public function verifyingOrder(User $user, Product $product): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [OrderStatus::Delivered->value, OrderStatus::Completed->value])
            ->whereHas('items.variant', fn ($q) => $q->where('product_id', $product->id))
            ->latest()
            ->first();
    }

    public function hasReviewed(User $user, Product $product): bool
    {
        return $product->reviews()->where('user_id', $user->id)->exists();
    }

    public function canReview(User $user, Product $product): bool
    {
        return ! $this->hasReviewed($user, $product) && $this->verifyingOrder($user, $product) !== null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(User $user, Product $product, array $data): Review
    {
        $review = $product->reviews()->create([
            'user_id' => $user->id,
            'order_id' => $this->verifyingOrder($user, $product)?->id,
            'rating' => (int) $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'status' => Review::STATUS_PENDING,
        ]);

        Notification::send(
            Admin::withAbility('manage_products'),
            new AdminAlertNotification(
                'Review awaiting moderation',
                "A new {$review->rating}★ review for {$product->name} is pending approval.",
                'important',
                route('admin.reviews.index'),
                'fa-star',
            ),
        );

        return $review;
    }

    public function approve(Review $review): void
    {
        $review->update(['status' => Review::STATUS_APPROVED]);
        $this->recalculate($review->product);
    }

    public function reject(Review $review): void
    {
        $review->update(['status' => Review::STATUS_REJECTED]);
        $this->recalculate($review->product);
    }

    /**
     * Recompute the product's cached aggregate rating from approved reviews.
     */
    public function recalculate(Product $product): void
    {
        $approved = $product->reviews()->approved();

        $product->forceFill([
            'rating_count' => (clone $approved)->count(),
            'rating_avg' => round((float) (clone $approved)->avg('rating'), 2),
        ])->save();
    }
}
