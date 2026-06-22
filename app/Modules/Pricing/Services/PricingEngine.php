<?php

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Pricing\ValueObjects\ResolvedPrice;
use App\Support\Money;

/**
 * The single source of truth for "what does this customer pay?".
 *
 * Pipeline:  base (retail) → wholesale / sale / scheduled promo → quantity-discount tier
 *            → ResolvedPrice  (cart-level coupons are applied later at checkout)
 *
 * Precedence for retail shoppers: retail → active promotional price (overrides
 * the sale price) → never charged above retail. Wholesale buyers follow the
 * wholesale + quantity-tier path; promos are a retail-facing campaign.
 *
 * All maths is in integer kobo via {@see Money}. VAT is intentionally NOT
 * applied here — it is computed at checkout by TaxCalculator so the unit
 * price stays a clean catalog concern.
 */
class PricingEngine
{
    public function priceForVariant(ProductVariant $variant, ?User $customer, int $quantity = 1): ResolvedPrice
    {
        $retail = $variant->retail_price ?? Money::zero();
        $unit = $retail;
        $isWholesale = false;
        $isOnSale = false;

        if ($customer !== null && $customer->isWholesale()) {
            $wholesale = $variant->wholesale_price ?? $retail;
            $tier = $this->bestTierPrice($variant->product, $quantity);
            $unit = $tier !== null ? $wholesale->min($tier) : $wholesale;
            $isWholesale = $unit->lessThan($retail);
        } else {
            // A live promo overrides the sale price; whichever is set wins, and
            // the customer always gets the lower of the two against retail.
            $promo = $variant->livePromotionalPrice();
            $sale = ($variant->sale_price !== null && $variant->sale_price->lessThan($retail))
                ? $variant->sale_price
                : null;

            $candidate = match (true) {
                $promo !== null && $sale !== null => $promo->min($sale),
                $promo !== null => $promo,
                default => $sale,
            };

            if ($candidate !== null && $candidate->lessThan($retail)) {
                $unit = $candidate;
                $isOnSale = true;
            }
        }

        return new ResolvedPrice(
            unitPrice: $unit,
            retailPrice: $retail,
            isWholesale: $isWholesale,
            isOnSale: $isOnSale,
        );
    }

    public function priceForProduct(Product $product, ?User $customer, int $quantity = 1): ResolvedPrice
    {
        $variant = $product->primaryVariant();

        if ($variant === null) {
            return new ResolvedPrice(Money::zero(), Money::zero());
        }

        // The engine reads quantityDiscounts off the variant's product.
        $variant->setRelation('product', $product);

        return $this->priceForVariant($variant, $customer, $quantity);
    }

    /**
     * Lowest tiered wholesale unit price applicable at this quantity, or null.
     */
    private function bestTierPrice(?Product $product, int $quantity): ?Money
    {
        if ($product === null) {
            return null;
        }

        $tiers = $product->relationLoaded('quantityDiscounts')
            ? $product->quantityDiscounts
            : $product->quantityDiscounts()->get();

        $applicable = $tiers->filter(fn ($tier) => $quantity >= $tier->min_qty);

        if ($applicable->isEmpty()) {
            return null;
        }

        return $applicable
            ->map(fn ($tier) => $tier->unit_price)
            ->reduce(fn (?Money $carry, Money $price) => $carry === null ? $price : $carry->min($price));
    }
}
