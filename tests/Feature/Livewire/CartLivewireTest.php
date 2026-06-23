<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Cart\CartCount;
use App\Livewire\Cart\CartManager;
use App\Models\User;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Models\Coupon;
use App\Modules\Pricing\Services\CouponService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartLivewireTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_per_card_add_returns_json_with_the_live_count(): void
    {
        // The product-card button is a real form enhanced by one delegated fetch
        // (no per-card Livewire) — cart.store answers JSON when asked.
        $product = Product::factory()->priced(5000)->stock(10)->create();

        $this->postJson(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['count' => 2])
            ->assertJsonStructure(['count', 'message']);

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => 2]);
    }

    public function test_per_card_add_still_redirects_without_json_no_js_fallback(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();

        $this->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $product->primaryVariant()->id]);
    }

    public function test_cart_count_reflects_the_cart_and_refreshes_on_event(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();

        $component = Livewire::test(CartCount::class)->assertSet('count', 0);

        app(CartService::class)->add($product->primaryVariant(), 2);

        $component->dispatch('cart-updated')->assertSet('count', 2);
    }

    public function test_cart_manager_changes_quantity_and_removes_items(): void
    {
        $product = Product::factory()->priced(2000)->stock(10)->create();
        $item = app(CartService::class)->add($product->primaryVariant(), 1);

        $manager = Livewire::test(CartManager::class)
            ->call('increment', $item->id)
            ->assertDispatched('cart-updated');

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 2]);

        $manager->call('remove', $item->id)->assertDispatched('cart-updated');
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_cart_manager_applies_and_removes_a_coupon(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->priced(5000)->stock(10)->create();
        Coupon::factory()->percentage(10)->create(['code' => 'SAVE10']);
        $this->actingAs($user);
        app(CartService::class)->add($product->primaryVariant(), 1);

        Livewire::test(CartManager::class)
            ->set('couponCode', 'SAVE10')
            ->call('applyCoupon')
            ->assertDispatched('toast')
            ->assertSet('couponCode', '');

        $this->assertSame('SAVE10', session(CouponService::SESSION_KEY));

        Livewire::test(CartManager::class)->call('removeCoupon');
        $this->assertNull(session(CouponService::SESSION_KEY));
    }

    public function test_the_cart_page_renders_the_livewire_component(): void
    {
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSeeLivewire(CartManager::class);
    }
}
