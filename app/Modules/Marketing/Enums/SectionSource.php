<?php

namespace App\Modules\Marketing\Enums;

enum SectionSource: string
{
    case Featured = 'featured';
    case Latest = 'latest';
    case BestSellers = 'best_sellers';
    case OnSale = 'on_sale';
    case Category = 'category';
    case Brand = 'brand';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Featured => 'Featured products',
            self::Latest => 'New arrivals',
            self::BestSellers => 'Best sellers',
            self::OnSale => 'On sale (active discounts)',
            self::Category => 'From a category',
            self::Brand => 'From a brand',
            self::Manual => 'Curated collection',
        };
    }

    /** Whether this source needs a source_ref (category / brand / collection id). */
    public function needsRef(): bool
    {
        return in_array($this, [self::Category, self::Brand, self::Manual], true);
    }
}
