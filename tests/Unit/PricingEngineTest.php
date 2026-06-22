<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Pricing\Services\PricingEngine;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    use LazilyRefreshDatabase;

    private PricingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PricingEngine;
    }

    public function test_guest_pays_retail(): void
    {
        $variant = new ProductVariant([
            'retail_price' => Money::fromNaira(1000),
            'wholesale_price' => Money::fromNaira(800),
            'sale_price' => null,
        ]);

        $price = $this->engine->priceForVariant($variant, null, 1);

        $this->assertSame(Money::fromNaira(1000)->kobo, $price->unitPrice->kobo);
        $this->assertFalse($price->isWholesale);
        $this->assertFalse($price->isOnSale);
    }

    public function test_retail_sale_price_applies(): void
    {
        $variant = new ProductVariant([
            'retail_price' => Money::fromNaira(1000),
            'sale_price' => Money::fromNaira(700),
        ]);

        $price = $this->engine->priceForVariant($variant, null, 1);

        $this->assertSame(Money::fromNaira(700)->kobo, $price->unitPrice->kobo);
        $this->assertTrue($price->isOnSale);
        $this->assertTrue($price->hasDiscount());
        $this->assertSame(30, $price->discountPercent());
    }

    public function test_wholesale_user_pays_wholesale(): void
    {
        $variant = new ProductVariant([
            'retail_price' => Money::fromNaira(1000),
            'wholesale_price' => Money::fromNaira(800),
        ]);
        $user = new User(['customer_type' => User::TYPE_WHOLESALE]);

        $price = $this->engine->priceForVariant($variant, $user, 1);

        $this->assertSame(Money::fromNaira(800)->kobo, $price->unitPrice->kobo);
        $this->assertTrue($price->isWholesale);
    }

    public function test_wholesale_user_without_wholesale_price_pays_retail(): void
    {
        $variant = new ProductVariant([
            'retail_price' => Money::fromNaira(1000),
            'wholesale_price' => null,
        ]);
        $user = new User(['customer_type' => User::TYPE_WHOLESALE]);

        $this->assertSame(
            Money::fromNaira(1000)->kobo,
            $this->engine->priceForVariant($variant, $user, 1)->unitPrice->kobo,
        );
    }

    public function test_quantity_tier_beats_wholesale_at_qualifying_qty(): void
    {
        $product = Product::factory()->priced(1000, 800)->create();
        $product->quantityDiscounts()->create(['min_qty' => 10, 'unit_price' => Money::fromNaira(700)]);

        $variant = $product->primaryVariant();
        $variant->setRelation('product', $product->load('quantityDiscounts'));
        $user = User::factory()->wholesale()->create();

        $this->assertSame(Money::fromNaira(800)->kobo, $this->engine->priceForVariant($variant, $user, 1)->unitPrice->kobo);
        $this->assertSame(Money::fromNaira(700)->kobo, $this->engine->priceForVariant($variant, $user, 10)->unitPrice->kobo);
    }

    public function test_price_for_product_uses_default_variant(): void
    {
        $product = Product::factory()->priced(1500)->create();

        $price = $this->engine->priceForProduct($product->load(['variants', 'quantityDiscounts']), null, 1);

        $this->assertSame(Money::fromNaira(1500)->kobo, $price->unitPrice->kobo);
    }
}
