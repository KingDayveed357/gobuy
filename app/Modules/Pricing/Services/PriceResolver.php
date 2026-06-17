<?php

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\ValueObjects\ResolvedPrice;

/**
 * Single source of truth for "what price does this customer pay?".
 *
 * Every place that needs a price — product cards, cart, checkout, orders —
 * resolves through here. Future tiered pricing changes only this class.
 */
class PriceResolver
{
    public function for(Product $product, ?User $customer, int $quantity = 1): ResolvedPrice
    {
        $retail = (float) $product->retail_price;

        if ($this->qualifiesForWholesale($product, $customer, $quantity)) {
            return new ResolvedPrice(
                unitPrice: (float) $product->wholesale_price,
                retailPrice: $retail,
                isWholesale: true,
            );
        }

        return new ResolvedPrice(
            unitPrice: $retail,
            retailPrice: $retail,
            isWholesale: false,
        );
    }

    private function qualifiesForWholesale(Product $product, ?User $customer, int $quantity): bool
    {
        return $customer !== null
            && $customer->isWholesale()
            && $product->wholesale_price !== null
            && $quantity >= $product->wholesale_min_qty;
    }
}
