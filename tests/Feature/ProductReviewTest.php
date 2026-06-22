<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Review\Models\Review;
use App\Modules\Review\Services\ReviewService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function deliveredOrderFor(User $user, Product $product): Order
    {
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Delivered]);
        $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => $product->name, 'sku' => $product->primaryVariant()->sku,
            'unit_price' => Money::fromNaira(1000), 'quantity' => 1, 'line_total' => Money::fromNaira(1000),
        ]);

        return $order;
    }

    public function test_a_verified_purchaser_can_submit_a_pending_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->stock(5)->create();
        $this->deliveredOrderFor($user, $product);

        $this->actingAs($user)->post(route('reviews.store', $product), [
            'rating' => 5, 'title' => 'Great', 'body' => 'Loved it.',
        ])->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id, 'user_id' => $user->id, 'rating' => 5, 'status' => 'pending',
        ]);
    }

    public function test_a_non_purchaser_cannot_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->stock(5)->create();

        $this->actingAs($user)->post(route('reviews.store', $product), ['rating' => 5])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_approving_a_review_updates_the_cached_aggregate(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->stock(5)->create();
        $review = $product->reviews()->create([
            'user_id' => $user->id, 'rating' => 4, 'body' => 'Good', 'status' => Review::STATUS_PENDING,
        ]);

        app(ReviewService::class)->approve($review);

        $product->refresh();
        $this->assertSame(1, $product->rating_count);
        $this->assertSame('4.00', (string) $product->rating_avg);
    }

    public function test_only_approved_reviews_count_toward_the_aggregate(): void
    {
        $product = Product::factory()->stock(5)->create();
        $service = app(ReviewService::class);

        $r1 = $product->reviews()->create(['user_id' => User::factory()->create()->id, 'rating' => 5, 'status' => 'pending']);
        $r2 = $product->reviews()->create(['user_id' => User::factory()->create()->id, 'rating' => 1, 'status' => 'pending']);
        $service->approve($r1);
        $service->reject($r2);

        $product->refresh();
        $this->assertSame(1, $product->rating_count);
        $this->assertSame('5.00', (string) $product->rating_avg);
    }
}
