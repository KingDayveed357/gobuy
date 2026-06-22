<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_home_page_renders(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_product_listing_shows_active_products(): void
    {
        $active = Product::factory()->create(['name' => 'Visible Widget']);
        $draft = Product::factory()->draft()->create(['name' => 'Hidden Widget']);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Visible Widget')
            ->assertDontSee('Hidden Widget');
    }

    public function test_product_detail_page_renders_for_active_product(): void
    {
        $product = Product::factory()->create();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_draft_product_detail_returns_404(): void
    {
        $product = Product::factory()->draft()->create();

        $this->get(route('products.show', $product))->assertNotFound();
    }

    public function test_listing_can_filter_by_category(): void
    {
        $electronics = Category::factory()->create(['slug' => 'electronics']);
        $groceries = Category::factory()->create(['slug' => 'groceries']);
        Product::factory()->for($electronics)->create(['name' => 'Phone Charger']);
        Product::factory()->for($groceries)->create(['name' => 'Rice Bag']);

        $this->get(route('products.index', ['category' => 'electronics']))
            ->assertOk()
            ->assertSee('Phone Charger')
            ->assertDontSee('Rice Bag');
    }

    public function test_listing_can_filter_by_brand_and_shows_a_removable_chip(): void
    {
        $apple = Brand::create(['name' => 'Apple', 'slug' => 'apple', 'is_active' => true]);
        $samsung = Brand::create(['name' => 'Samsung', 'slug' => 'samsung', 'is_active' => true]);
        Product::factory()->for($apple)->create(['name' => 'iThing']);
        Product::factory()->for($samsung)->create(['name' => 'GalaxyThing']);

        $this->get(route('products.index', ['brand' => 'apple']))
            ->assertOk()
            ->assertSee('iThing')
            ->assertDontSee('GalaxyThing')
            ->assertSee('Apple');           // chip label
    }
}
