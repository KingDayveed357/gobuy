<?php

namespace App\Modules\Pricing\Services;

use App\Modules\Catalog\Models\Product;
use App\Support\Money;

/**
 * VAT handling at checkout. Supports inclusive (price already contains VAT —
 * the amount is extracted for the records) and exclusive (VAT added on top).
 * All maths in integer kobo via {@see Money}.
 */
class TaxCalculator
{
    public function lineVat(Product $product, Money $lineTotal): Money
    {
        if ($product->is_tax_exempt) {
            return Money::zero();
        }

        $rate = (float) $product->vat_rate;

        if ($rate <= 0) {
            return Money::zero();
        }

        $fraction = $rate / 100;

        if ($product->is_vat_inclusive) {
            // Extract the VAT already contained in the line total.
            $net = (int) round($lineTotal->kobo / (1 + $fraction));

            return Money::fromKobo($lineTotal->kobo - $net);
        }

        // Add VAT on top.
        return $lineTotal->percentage($rate);
    }

    /**
     * Whether VAT is added on top of the line (exclusive) vs already included.
     */
    public function isExclusive(Product $product): bool
    {
        return ! $product->is_tax_exempt
            && ! $product->is_vat_inclusive
            && (float) $product->vat_rate > 0;
    }
}
