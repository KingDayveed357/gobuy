<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Services\PricingEngine;
use App\Modules\Pricing\Services\PromotionService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class PromotionalPricingTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    public function test_a_live_promo_overrides_retail_and_sale_for_a_shopper(): void
    {
        $product = Product::factory()->priced(10000)->stock(5)->create();
        $variant = $product->primaryVariant();
        $variant->update(['sale_price' => Money::fromNaira(9000)]);

        $variant->promotionalPrices()->create([
            'label' => 'Flash',
            'price' => Money::fromNaira(7000),
            'is_active' => true,
        ]);

        $resolved = app(PricingEngine::class)->priceForVariant($variant->fresh(), null);

        $this->assertSame(Money::fromNaira(7000)->kobo, $resolved->unitPrice->kobo);
        $this->assertTrue($resolved->isOnSale);
        $this->assertSame(Money::fromNaira(10000)->kobo, $resolved->retailPrice->kobo);
    }

    public function test_a_promo_outside_its_window_is_ignored(): void
    {
        $product = Product::factory()->priced(10000)->stock(5)->create();
        $variant = $product->primaryVariant();

        $variant->promotionalPrices()->create([
            'price' => Money::fromNaira(6000),
            'is_active' => true,
            'starts_at' => now()->addDay(),   // not started yet
            'ends_at' => now()->addDays(2),
        ]);

        $resolved = app(PricingEngine::class)->priceForVariant($variant->fresh(), null);

        $this->assertSame(Money::fromNaira(10000)->kobo, $resolved->unitPrice->kobo);
        $this->assertFalse($resolved->isOnSale);
    }

    public function test_scheduling_a_percentage_promotion_creates_per_variant_prices(): void
    {
        $product = Product::factory()->priced(20000)->stock(5)->create();

        app(PromotionService::class)->scheduleForProduct($product, [
            'discount_type' => 'percentage',
            'value' => 25,
            'label' => 'Quarter off',
        ]);

        // 25% off ₦20,000 = ₦15,000.
        $this->assertDatabaseHas('promotional_prices', [
            'product_variant_id' => $product->primaryVariant()->id,
            'price' => 1500000,
        ]);

        $resolved = app(PricingEngine::class)->priceForVariant($product->primaryVariant()->fresh(), null);
        $this->assertSame(1500000, $resolved->unitPrice->kobo);
    }

    public function test_admin_can_schedule_and_remove_a_promotion(): void
    {
        $product = Product::factory()->priced(5000)->stock(5)->create();

        $this->actingAsAdmin('Super Admin');

        $this->post(route('admin.promotions.store'), [
            'product_id' => $product->id,
            'discount_type' => 'percentage',
            'value' => 10,
        ])->assertRedirect(route('admin.promotions.index'));

        $this->assertDatabaseHas('promotional_prices', ['product_variant_id' => $product->primaryVariant()->id]);

        $this->delete(route('admin.promotions.destroy', $product))
            ->assertRedirect(route('admin.promotions.index'));

        $this->assertDatabaseMissing('promotional_prices', ['product_variant_id' => $product->primaryVariant()->id]);
    }
}
