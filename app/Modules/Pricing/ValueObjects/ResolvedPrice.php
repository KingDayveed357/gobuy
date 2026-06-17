<?php

namespace App\Modules\Pricing\ValueObjects;

/**
 * Immutable result of a price resolution.
 *
 * Not a request DTO — a lightweight value object so the UI and cart/order
 * math have a single, consistent shape to read from.
 */
final class ResolvedPrice
{
    public function __construct(
        public readonly float $unitPrice,
        public readonly float $retailPrice,
        public readonly bool $isWholesale,
    ) {}

    public function lineTotal(int $quantity): float
    {
        return round($this->unitPrice * $quantity, 2);
    }

    public function hasDiscount(): bool
    {
        return $this->isWholesale && $this->unitPrice < $this->retailPrice;
    }
}
