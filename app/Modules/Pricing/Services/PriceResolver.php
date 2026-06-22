<?php

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Pricing\ValueObjects\ResolvedPrice;

/**
 * Thin facade over the PricingEngine. Keeps a stable, convenient API for
 * callers that have a Product (cards) or a ProductVariant (cart/checkout).
 */
class PriceResolver
{
    public function __construct(private readonly PricingEngine $engine) {}

    public function for(Product $product, ?User $customer, int $quantity = 1): ResolvedPrice
    {
        return $this->engine->priceForProduct($product, $customer, $quantity);
    }

    public function forVariant(ProductVariant $variant, ?User $customer, int $quantity = 1): ResolvedPrice
    {
        return $this->engine->priceForVariant($variant, $customer, $quantity);
    }
}
