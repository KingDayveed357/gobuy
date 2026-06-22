<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccountPolishTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_customer_can_reorder_a_past_order_into_their_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->priced(2000)->stock(10)->create();
        $variant = $product->primaryVariant();

        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Delivered]);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'name' => $product->name,
            'sku' => $variant->sku,
            'unit_price' => Money::fromNaira(2000),
            'quantity' => 2,
            'line_total' => Money::fromNaira(4000),
        ]);

        $this->actingAs($user)->post(route('account.orders.reorder', $order))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 2]);
    }

    public function test_reorder_is_blocked_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)->post(route('account.orders.reorder', $order))->assertForbidden();
    }

    public function test_product_page_shows_a_recently_viewed_strip_from_the_cookie(): void
    {
        $seen = Product::factory()->create(['name' => 'Previously Seen Gadget']);
        $current = Product::factory()->create(['name' => 'Current Gadget']);

        $this->withCookie('recently_viewed', (string) $seen->id)
            ->get(route('products.show', $current))
            ->assertOk()
            ->assertSee('Recently viewed')
            ->assertSee('Previously Seen Gadget');
    }

    public function test_viewing_a_product_queues_it_into_the_recently_viewed_cookie(): void
    {
        $product = Product::factory()->create();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertCookie('recently_viewed', (string) $product->id);
    }
}
