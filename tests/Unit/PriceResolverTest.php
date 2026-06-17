<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Services\PriceResolver;
use PHPUnit\Framework\TestCase;

class PriceResolverTest extends TestCase
{
    private PriceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PriceResolver;
    }

    public function test_guest_pays_retail_price(): void
    {
        $product = new Product(['retail_price' => 1000, 'wholesale_price' => 800, 'wholesale_min_qty' => 5]);

        $price = $this->resolver->for($product, null, 10);

        $this->assertSame(1000.0, $price->unitPrice);
        $this->assertFalse($price->isWholesale);
    }

    public function test_retail_customer_never_gets_wholesale_price(): void
    {
        $product = new Product(['retail_price' => 1000, 'wholesale_price' => 800, 'wholesale_min_qty' => 5]);
        $customer = new User(['customer_type' => User::TYPE_RETAIL]);

        $price = $this->resolver->for($product, $customer, 50);

        $this->assertSame(1000.0, $price->unitPrice);
        $this->assertFalse($price->isWholesale);
    }

    public function test_wholesale_customer_below_min_qty_pays_retail(): void
    {
        $product = new Product(['retail_price' => 1000, 'wholesale_price' => 800, 'wholesale_min_qty' => 5]);
        $customer = new User(['customer_type' => User::TYPE_WHOLESALE]);

        $price = $this->resolver->for($product, $customer, 4);

        $this->assertSame(1000.0, $price->unitPrice);
        $this->assertFalse($price->isWholesale);
    }

    public function test_wholesale_customer_at_or_above_min_qty_pays_wholesale(): void
    {
        $product = new Product(['retail_price' => 1000, 'wholesale_price' => 800, 'wholesale_min_qty' => 5]);
        $customer = new User(['customer_type' => User::TYPE_WHOLESALE]);

        $price = $this->resolver->for($product, $customer, 5);

        $this->assertSame(800.0, $price->unitPrice);
        $this->assertTrue($price->isWholesale);
        $this->assertTrue($price->hasDiscount());
        $this->assertSame(4000.0, $price->lineTotal(5));
    }

    public function test_wholesale_customer_without_wholesale_price_pays_retail(): void
    {
        $product = new Product(['retail_price' => 1000, 'wholesale_price' => null, 'wholesale_min_qty' => 5]);
        $customer = new User(['customer_type' => User::TYPE_WHOLESALE]);

        $price = $this->resolver->for($product, $customer, 10);

        $this->assertSame(1000.0, $price->unitPrice);
        $this->assertFalse($price->isWholesale);
    }
}
