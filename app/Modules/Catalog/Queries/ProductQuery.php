<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lightweight, chainable query object for product listings. Pricing, SKU and
 * stock live on variants, so price/sku filters reach through the default
 * variant via correlated subqueries.
 */
class ProductQuery
{
    private Builder $query;

    public function __construct()
    {
        // `variants.promotionalPrices` is eager-loaded so PricingEngine's
        // livePromotionalPrice() check doesn't lazy-load per variant (N+1 across
        // every card on a listing page).
        $this->query = Product::query()->with(['category', 'brand', 'media', 'variants.promotionalPrices', 'quantityDiscounts']);
    }

    public static function make(): self
    {
        return new self;
    }

    public function active(): self
    {
        $this->query->active();

        return $this;
    }

    public function search(?string $term): self
    {
        if (filled($term)) {
            $this->query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('variants', fn (Builder $v) => $v->where('sku', 'like', "%{$term}%"));
            });
        }

        return $this;
    }

    public function inCategory(?string $categorySlugOrId): self
    {
        if (filled($categorySlugOrId)) {
            $this->query->whereHas('category', function (Builder $q) use ($categorySlugOrId): void {
                is_numeric($categorySlugOrId)
                    ? $q->where('id', $categorySlugOrId)
                    : $q->where('slug', $categorySlugOrId);
            });
        }

        return $this;
    }

    public function inBrand(?string $brandSlugOrId): self
    {
        if (filled($brandSlugOrId)) {
            $this->query->whereHas('brand', function (Builder $q) use ($brandSlugOrId): void {
                is_numeric($brandSlugOrId)
                    ? $q->where('id', $brandSlugOrId)
                    : $q->where('slug', $brandSlugOrId);
            });
        }

        return $this;
    }

    public function withStatus(?string $status): self
    {
        if (filled($status) && $status !== 'all') {
            $this->query->where('status', $status);
        }

        return $this;
    }

    public function inStockOnly(bool $only): self
    {
        if ($only) {
            $this->query->whereHas('variants', fn (Builder $q) => $q->where('stock', '>', 0));
        }

        return $this;
    }

    public function priceBetween(?int $min, ?int $max): self
    {
        if ($min !== null || $max !== null) {
            $this->query->whereHas('variants', function (Builder $q) use ($min, $max): void {
                if ($min !== null) {
                    $q->where('retail_price', '>=', $min);
                }
                if ($max !== null) {
                    $q->where('retail_price', '<=', $max);
                }
            });
        }

        return $this;
    }

    public function sort(?string $sort): self
    {
        $defaultPrice = ProductVariant::select('retail_price')
            ->whereColumn('product_id', 'products.id')
            ->orderByDesc('is_default')->orderBy('id')
            ->limit(1);

        match ($sort) {
            'price_asc' => $this->query->orderBy($defaultPrice),
            'price_desc' => $this->query->orderByDesc($defaultPrice),
            'name' => $this->query->orderBy('name'),
            default => $this->query->latest(),
        };

        return $this;
    }

    public function builder(): Builder
    {
        return $this->query;
    }

    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        return $this->query->paginate($perPage)->withQueryString();
    }
}
