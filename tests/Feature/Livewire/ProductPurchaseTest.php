<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Product\ProductPurchase;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductPurchaseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pdp_add_uses_the_shared_cart_service_and_emits_events(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();
        $variant = $product->primaryVariant();

        Livewire::test(ProductPurchase::class, ['product' => $product, 'selectedId' => $variant->id, 'stock' => 10])
            ->call('add', $variant->id, 3)
            ->assertDispatched('cart-updated')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 3]);
    }

    public function test_pdp_add_sets_an_absolute_quantity_like_the_legacy_route(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();
        $variant = $product->primaryVariant();
        app(CartService::class)->add($variant, 2);

        Livewire::test(ProductPurchase::class, ['product' => $product, 'selectedId' => $variant->id, 'stock' => 10])
            ->call('add', $variant->id, 5);

        // Absolute set (not increment): 2 → 5.
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 5]);
    }

    public function test_pdp_add_rejects_a_variant_from_another_product(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();
        $other = Product::factory()->priced(5000)->stock(10)->create();

        Livewire::test(ProductPurchase::class, ['product' => $product, 'selectedId' => $product->primaryVariant()->id, 'stock' => 10])
            ->call('add', $other->primaryVariant()->id, 1)
            ->assertDispatched('toast')
            ->assertNotDispatched('cart-updated');

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_product_page_renders_the_purchase_component(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSeeLivewire(ProductPurchase::class);
    }
}
