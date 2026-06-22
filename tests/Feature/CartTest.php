<?php

namespace Tests\Feature;

use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function variantId(Product $product): int
    {
        return $product->primaryVariant()->id;
    }

    public function test_empty_cart_page_renders(): void
    {
        $this->get(route('cart.index'))->assertOk()->assertSee('Your cart is empty');
    }

    public function test_guest_can_add_a_product_to_the_cart(): void
    {
        $product = Product::factory()->stock(50)->create(['name' => 'Cart Widget']);
        $vid = $this->variantId($product);

        $this->post(route('cart.store'), ['product_variant_id' => $vid, 'quantity' => 3])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $vid, 'quantity' => 3]);
        $this->get(route('cart.index'))->assertSee('Cart Widget');
    }

    public function test_adding_same_variant_twice_increments_quantity(): void
    {
        $product = Product::factory()->stock(50)->create();
        $vid = $this->variantId($product);

        $this->post(route('cart.store'), ['product_variant_id' => $vid, 'quantity' => 2]);
        $this->post(route('cart.store'), ['product_variant_id' => $vid, 'quantity' => 4]);

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $vid, 'quantity' => 6]);
    }

    public function test_quantity_is_capped_at_available_stock(): void
    {
        $product = Product::factory()->stock(5)->create();
        $vid = $this->variantId($product);

        $this->post(route('cart.store'), ['product_variant_id' => $vid, 'quantity' => 99]);

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $vid, 'quantity' => 5]);
    }

    public function test_inactive_product_cannot_be_added(): void
    {
        $product = Product::factory()->draft()->create();

        $this->post(route('cart.store'), ['product_variant_id' => $this->variantId($product), 'quantity' => 1])
            ->assertNotFound();
    }

    public function test_updating_item_to_zero_removes_it(): void
    {
        $product = Product::factory()->stock(10)->create();
        $vid = $this->variantId($product);
        $this->post(route('cart.store'), ['product_variant_id' => $vid, 'quantity' => 2]);
        $cartItemId = CartItem::firstWhere('product_variant_id', $vid)->id;

        $this->patch(route('cart.items.update', $cartItemId), ['quantity' => 0])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItemId]);
    }

    public function test_item_can_be_removed(): void
    {
        $product = Product::factory()->stock(10)->create();
        $vid = $this->variantId($product);
        $this->post(route('cart.store'), ['product_variant_id' => $vid, 'quantity' => 1]);
        $cartItemId = CartItem::firstWhere('product_variant_id', $vid)->id;

        $this->delete(route('cart.items.destroy', $cartItemId))
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItemId]);
    }

    public function test_cannot_modify_an_item_from_another_cart(): void
    {
        $product = Product::factory()->stock(10)->create();
        $vid = $this->variantId($product);
        $this->post(route('cart.store'), ['product_variant_id' => $vid, 'quantity' => 1]);

        $otherCart = Cart::factory()->create();
        $otherItem = $otherCart->items()->create(['product_variant_id' => $vid, 'quantity' => 1]);

        $this->patch(route('cart.items.update', $otherItem->id), ['quantity' => 5])
            ->assertForbidden();
    }
}
