<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Services\PriceResolver;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PriceResolverTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_for_product_prices_the_default_variant(): void
    {
        $product = Product::factory()->priced(1000, 800)->create()->load(['variants', 'quantityDiscounts']);
        $resolver = app(PriceResolver::class);

        $this->assertSame(Money::fromNaira(1000)->kobo, $resolver->for($product, null)->unitPrice->kobo);
    }

    public function test_for_variant_applies_wholesale(): void
    {
        $product = Product::factory()->priced(1000, 800)->create()->load(['variants', 'quantityDiscounts']);
        $variant = $product->primaryVariant();
        $variant->setRelation('product', $product);
        $resolver = app(PriceResolver::class);

        $wholesale = User::factory()->wholesale()->create();

        $this->assertSame(Money::fromNaira(1000)->kobo, $resolver->forVariant($variant, null)->unitPrice->kobo);
        $this->assertSame(Money::fromNaira(800)->kobo, $resolver->forVariant($variant, $wholesale)->unitPrice->kobo);
    }
}
