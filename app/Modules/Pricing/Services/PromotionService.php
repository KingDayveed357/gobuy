<?php

namespace App\Modules\Pricing\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Models\PromotionalPrice;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Schedules time-bound promotions. A promotion is authored at the product level
 * (a percentage off, or a flat promo price) and expanded into one
 * {@see PromotionalPrice} row per variant so the pricing engine stays per-variant.
 */
class PromotionService
{
    /**
     * Create a promotion for every variant of a product. Any existing promo on
     * those variants is replaced (one campaign per product at a time).
     *
     * @param  array{discount_type: string, value: float, label?: string|null, starts_at?: string|CarbonInterface|null, ends_at?: string|CarbonInterface|null}  $data
     */
    public function scheduleForProduct(Product $product, array $data): void
    {
        $variants = $product->variants()->get();

        DB::transaction(function () use ($variants, $data): void {
            foreach ($variants as $variant) {
                $variant->promotionalPrices()->delete();

                $retail = $variant->retail_price ?? Money::zero();
                $price = $data['discount_type'] === 'percentage'
                    ? $retail->minus($retail->percentage((float) $data['value']))
                    : Money::fromNaira($data['value']);

                // Never schedule a promo at or above retail — it would be a no-op.
                if (! $price->lessThan($retail)) {
                    continue;
                }

                $variant->promotionalPrices()->create([
                    'label' => $data['label'] ?? null,
                    'price' => $price,
                    'is_active' => true,
                    'starts_at' => $data['starts_at'] ?? null,
                    'ends_at' => $data['ends_at'] ?? null,
                ]);
            }
        });
    }

    /**
     * Remove the promotion from every variant of a product.
     */
    public function endForProduct(Product $product): void
    {
        PromotionalPrice::whereIn('product_variant_id', $product->variants()->select('id'))->delete();
    }
}
