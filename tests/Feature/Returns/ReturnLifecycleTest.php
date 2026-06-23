<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Returns\Models\ReturnRequest;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReturnLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // These tests cover request mechanics, not approval policy — keep new
        // returns in the manual-review queue regardless of risk score.
        config(['gobuy.returns.auto_approve.enabled' => false]);
    }

    private function deliveredOrderItem(User $user, int $qty = 2): OrderItem
    {
        $product = Product::factory()->stock(5)->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Delivered,
            'delivered_at' => now()->subDay(),
        ]);

        return $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => $product->name,
            'sku' => $product->primaryVariant()->sku,
            'unit_price' => Money::fromNaira(2000),
            'quantity' => $qty,
            'line_total' => Money::fromNaira(2000 * $qty),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(OrderItem $item, int $qty = 1): array
    {
        return [
            'reason_code' => 'changed_mind',
            'refund_destination' => 'store_credit',
            'idempotency_key' => 'idem-'.uniqid(),
            'items' => [
                $item->id => ['order_item_id' => $item->id, 'selected' => '1', 'quantity' => $qty, 'condition_reported' => 'unopened'],
            ],
        ];
    }

    public function test_a_customer_can_request_a_return(): void
    {
        $user = User::factory()->create();
        $item = $this->deliveredOrderItem($user);

        $this->actingAs($user)
            ->post(route('account.returns.store', $item->order), $this->payload($item, 1))
            ->assertRedirect();

        $this->assertDatabaseHas('return_requests', ['order_id' => $item->order_id, 'status' => 'requested', 'refund_destination' => 'store_credit']);
        $this->assertDatabaseHas('return_items', ['order_item_id' => $item->id, 'quantity' => 1, 'unit_price_snapshot' => 200000]);
        $this->assertDatabaseHas('return_events', ['action' => 'created']);
    }

    public function test_return_creation_is_idempotent_on_the_key(): void
    {
        $user = User::factory()->create();
        $item = $this->deliveredOrderItem($user);
        $payload = $this->payload($item, 1);

        $this->actingAs($user)->post(route('account.returns.store', $item->order), $payload)->assertRedirect();
        $this->actingAs($user)->post(route('account.returns.store', $item->order), $payload)->assertRedirect();

        $this->assertSame(1, ReturnRequest::count());
    }

    public function test_a_customer_cannot_return_more_than_purchased(): void
    {
        $user = User::factory()->create();
        $item = $this->deliveredOrderItem($user, qty: 2);

        $this->actingAs($user)
            ->post(route('account.returns.store', $item->order), $this->payload($item, 5))
            ->assertSessionHas('error');

        $this->assertSame(0, ReturnRequest::count());
    }

    public function test_returns_are_blocked_on_an_undelivered_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->stock(5)->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Processing]);
        $item = $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => $product->name, 'sku' => $product->primaryVariant()->sku,
            'unit_price' => Money::fromNaira(2000), 'quantity' => 1, 'line_total' => Money::fromNaira(2000),
        ]);

        $this->actingAs($user)
            ->get(route('account.returns.create', $order))
            ->assertRedirect(route('account.orders'));
    }

    public function test_a_customer_cannot_view_another_users_return(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $item = $this->deliveredOrderItem($owner);
        $return = ReturnRequest::factory()->create(['order_id' => $item->order_id, 'user_id' => $owner->id]);

        $this->actingAs($intruder)->get(route('account.returns.show', $return))->assertForbidden();
    }

    public function test_a_customer_can_cancel_an_open_return(): void
    {
        $user = User::factory()->create();
        $item = $this->deliveredOrderItem($user);
        $return = ReturnRequest::factory()->create(['order_id' => $item->order_id, 'user_id' => $user->id]);

        $this->actingAs($user)->post(route('account.returns.cancel', $return))->assertRedirect();

        $this->assertSame('cancelled', $return->fresh()->status->value);
    }
}
