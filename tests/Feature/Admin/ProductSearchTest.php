<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_it_returns_matching_variants_with_rich_metadata(): void
    {
        $brand = Brand::create(['name' => 'Guinness', 'slug' => 'guinness', 'is_active' => true]);
        Product::factory()->priced(1200, 950)->stock(48)->create([
            'name' => 'Guinness Stout',
            'brand_id' => $brand->id,
        ]);
        Product::factory()->create(['name' => 'Unrelated Widget']);

        $this->getJson(route('admin.products.search', ['q' => 'guinness']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Guinness Stout')
            ->assertJsonPath('data.0.brand', 'Guinness')
            ->assertJsonPath('data.0.stock', 48)
            ->assertJsonMissing(['name' => 'Unrelated Widget']);
    }

    public function test_it_matches_on_sku(): void
    {
        Product::factory()->sku('BEER-001')->create(['name' => 'Star Lager']);

        $this->getJson(route('admin.products.search', ['q' => 'BEER-001']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Star Lager');
    }

    public function test_short_queries_return_nothing(): void
    {
        Product::factory()->create(['name' => 'Malta Guinness']);

        $this->getJson(route('admin.products.search', ['q' => 'M']))
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_in_stock_filter_excludes_out_of_stock_variants(): void
    {
        Product::factory()->outOfStock()->create(['name' => 'Sold Out Soda']);

        $this->getJson(route('admin.products.search', ['q' => 'soda', 'in_stock' => 1]))
            ->assertOk()
            ->assertExactJson(['data' => []]);

        $this->getJson(route('admin.products.search', ['q' => 'soda']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Sold Out Soda');
    }

    public function test_it_reports_wholesale_price_when_set(): void
    {
        Product::factory()->priced(2000, 1600)->create(['name' => 'Bulk Water']);

        $this->getJson(route('admin.products.search', ['q' => 'bulk water']))
            ->assertOk()
            ->assertJsonPath('data.0.wholesale', '₦1,600.00');
    }

    public function test_it_requires_an_authenticated_admin(): void
    {
        auth('admin')->logout();

        $this->get(route('admin.products.search', ['q' => 'anything']))
            ->assertRedirect(route('admin.login'));
    }
}
