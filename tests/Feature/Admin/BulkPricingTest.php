<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Services\BulkPricingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class BulkPricingTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    public function test_preview_does_not_persist_changes(): void
    {
        $product = Product::factory()->priced(10000)->stock(5)->create();

        $preview = app(BulkPricingService::class)->preview([
            'field' => 'retail_price',
            'direction' => 'increase',
            'method' => 'percentage',
            'value' => 10,
        ]);

        $this->assertSame(1, $preview['count']);
        $this->assertSame(1100000, $preview['rows']->first()['new']->kobo);
        // Unchanged in the database.
        $this->assertSame(1000000, $product->primaryVariant()->fresh()->retail_price->kobo);
    }

    public function test_applying_a_percentage_increase_updates_prices_and_logs_history(): void
    {
        $this->actingAsAdmin('Super Admin');
        $product = Product::factory()->priced(10000)->stock(5)->create();

        $this->post(route('admin.pricing.bulk.store'), [
            'field' => 'retail_price',
            'direction' => 'increase',
            'method' => 'percentage',
            'value' => 10,
            'reason' => 'Supplier increase',
        ])->assertRedirect(route('admin.pricing.bulk.create'));

        $this->assertSame(1100000, $product->primaryVariant()->fresh()->retail_price->kobo);
        $this->assertDatabaseHas('price_histories', [
            'priceable_id' => $product->primaryVariant()->id,
            'field' => 'retail_price',
            'old_value' => 1000000,
            'new_value' => 1100000,
            'reason' => 'Supplier increase',
        ]);
    }

    public function test_a_category_filter_only_touches_that_category(): void
    {
        $this->actingAsAdmin('Super Admin');
        $target = Category::factory()->create();
        $other = Category::factory()->create();
        $in = Product::factory()->priced(5000)->stock(5)->create(['category_id' => $target->id]);
        $out = Product::factory()->priced(5000)->stock(5)->create(['category_id' => $other->id]);

        $this->post(route('admin.pricing.bulk.store'), [
            'category_id' => $target->id,
            'field' => 'retail_price',
            'direction' => 'decrease',
            'method' => 'fixed',
            'value' => 1000,
        ])->assertRedirect();

        $this->assertSame(400000, $in->primaryVariant()->fresh()->retail_price->kobo);
        $this->assertSame(500000, $out->primaryVariant()->fresh()->retail_price->kobo);
    }

    public function test_a_decrease_never_produces_a_negative_price(): void
    {
        $product = Product::factory()->priced(500)->stock(5)->create();

        $new = app(BulkPricingService::class)->newValueFor(
            $product->primaryVariant(),
            ['field' => 'retail_price', 'direction' => 'decrease', 'method' => 'fixed', 'value' => 9999],
        );

        $this->assertSame(0, $new->kobo);
    }
}
