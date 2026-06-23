<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStatusService;
use App\Modules\Returns\Services\ReturnEligibilityService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReturnEligibilityTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function deliveredOrder(User $user, Product $product, int $qty = 1, int $daysAgo = 1): Order
    {
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Delivered,
            'delivered_at' => now()->subDays($daysAgo),
        ]);
        $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => $product->name,
            'sku' => $product->primaryVariant()->sku,
            'unit_price' => Money::fromNaira(2000),
            'quantity' => $qty,
            'line_total' => Money::fromNaira(2000 * $qty),
        ]);

        return $order;
    }

    public function test_delivered_at_is_stamped_when_an_order_is_marked_delivered(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Shipped]);
        $this->assertNull($order->delivered_at);

        app(OrderStatusService::class)->transitionTo($order, OrderStatus::Delivered);

        $this->assertNotNull($order->fresh()->delivered_at);
    }

    public function test_a_recently_delivered_order_is_eligible(): void
    {
        $user = User::factory()->create();
        $order = $this->deliveredOrder($user, Product::factory()->stock(5)->create());

        $result = app(ReturnEligibilityService::class)->forOrder($order, $user);

        $this->assertTrue($result['eligible']);
        $this->assertCount(1, $result['items']);
    }

    public function test_an_order_past_the_window_is_blocked(): void
    {
        $user = User::factory()->create();
        // Default window is 14 days; delivered 30 days ago.
        $order = $this->deliveredOrder($user, Product::factory()->stock(5)->create(), daysAgo: 30);

        $result = app(ReturnEligibilityService::class)->forOrder($order, $user);

        $this->assertFalse($result['eligible']);
    }

    public function test_a_non_returnable_product_is_blocked(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->stock(5)->create(['is_returnable' => false]);
        $order = $this->deliveredOrder($user, $product);

        $result = app(ReturnEligibilityService::class)->forOrder($order, $user);

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('non-returnable', $result['blocked'][0]['reason']);
    }

    public function test_an_undelivered_order_is_blocked(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Processing]);

        $result = app(ReturnEligibilityService::class)->forOrder($order, $user);

        $this->assertFalse($result['eligible']);
    }

    public function test_another_users_order_is_blocked(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $order = $this->deliveredOrder($owner, Product::factory()->stock(5)->create());

        $result = app(ReturnEligibilityService::class)->forOrder($order, $intruder);

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('does not belong', $result['reason']);
    }

    public function test_a_fully_returned_line_is_blocked(): void
    {
        $user = User::factory()->create();
        $order = $this->deliveredOrder($user, Product::factory()->stock(5)->create(), qty: 2);
        $order->items()->first()->update(['returned_quantity' => 2]);

        $result = app(ReturnEligibilityService::class)->forOrder($order, $user);

        $this->assertFalse($result['eligible']);
    }
}
