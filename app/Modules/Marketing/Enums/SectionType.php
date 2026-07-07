<?php

namespace App\Modules\Marketing\Enums;

enum SectionType: string
{
    case ProductRail = 'product_rail';
    case ProductGrid = 'product_grid';
    case CountdownDeal = 'countdown_deal';
    case CategoryGrid = 'category_grid';
    case BrandRail = 'brand_rail';
    case BannerRow = 'banner_row';
    case RichText = 'rich_text';
    case EditorialMedia = 'editorial_media';

    public function label(): string
    {
        return match ($this) {
            self::ProductRail => 'Product rail (carousel)',
            self::ProductGrid => 'Product grid',
            self::CountdownDeal => 'Flash sale (countdown rail)',
            self::CategoryGrid => 'Category grid',
            self::BrandRail => 'Brand rail',
            self::BannerRow => 'Banner row',
            self::RichText => 'Editorial — text band',
            self::EditorialMedia => 'Editorial — image + copy',
        };
    }

    /** Whether this type draws from a product source (needs a SectionSource). */
    public function usesProductSource(): bool
    {
        return in_array($this, [self::ProductRail, self::ProductGrid, self::CountdownDeal], true);
    }

    /**
     * Editorial (story) blocks carry their own copy/media in `settings` rather
     * than resolving a content collection — so they render even with no items.
     */
    public function isEditorial(): bool
    {
        return in_array($this, [self::RichText, self::EditorialMedia], true);
    }
}
