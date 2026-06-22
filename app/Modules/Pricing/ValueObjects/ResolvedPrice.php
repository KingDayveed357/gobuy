<?php

namespace App\Modules\Pricing\ValueObjects;

use App\Support\Money;

/**
 * Immutable result of the pricing pipeline — the single shape the UI,
 * cart, and order math read from. All amounts are {@see Money} (kobo).
 */
final class ResolvedPrice
{
    public function __construct(
        public readonly Money $unitPrice,    // what the customer pays per unit
        public readonly Money $retailPrice,  // list price, for strike-through
        public readonly bool $isWholesale = false,
        public readonly bool $isOnSale = false,
    ) {}

    public function lineTotal(int $quantity): Money
    {
        return $this->unitPrice->times($quantity);
    }

    public function hasDiscount(): bool
    {
        return $this->unitPrice->lessThan($this->retailPrice);
    }

    public function discountPercent(): int
    {
        if (! $this->retailPrice->isPositive() || ! $this->unitPrice->lessThan($this->retailPrice)) {
            return 0;
        }

        return (int) round(
            (($this->retailPrice->kobo - $this->unitPrice->kobo) / $this->retailPrice->kobo) * 100
        );
    }
}
