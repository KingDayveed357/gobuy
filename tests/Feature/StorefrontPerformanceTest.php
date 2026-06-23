<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StorefrontPerformanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_listing_does_not_n_plus_one_on_promotional_prices(): void
    {
        // Several products, each with a live promo → without eager loading,
        // PricingEngine would hit promotional_prices once per card.
        Product::factory()->count(5)->priced(5000)->stock(10)->create()->each(function (Product $p): void {
            $p->primaryVariant()->promotionalPrices()->create(['price' => Money::fromNaira(4000), 'is_active' => true]);
        });

        DB::enableQueryLog();
        $this->get(route('products.index'))->assertOk();
        $promoQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'promotional_prices'))
            ->count();
        DB::disableQueryLog();

        // Eager-loaded → one batched query for all variants, not one per product.
        $this->assertLessThanOrEqual(1, $promoQueries);
    }

    public function test_product_page_does_not_n_plus_one_on_promotional_prices(): void
    {
        $product = Product::factory()->priced(5000)->stock(10)->create();
        $product->primaryVariant()->promotionalPrices()->create(['price' => Money::fromNaira(4000), 'is_active' => true]);

        DB::enableQueryLog();
        $this->get(route('products.show', $product))->assertOk();
        $promoQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'promotional_prices'))
            ->count();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(1, $promoQueries);
    }
}
