<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lightweight, chainable query object for product listings.
 *
 * Used by the storefront and admin to filter/search without scattering
 * query logic across controllers. Not a repository — it wraps the Eloquent
 * builder and returns it, so callers keep full Eloquent power.
 */
class ProductQuery
{
    private Builder $query;

    public function __construct()
    {
        $this->query = Product::query()->with(['category', 'images']);
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
                    ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        return $this;
    }

    public function inCategory(?string $categorySlug): self
    {
        if (filled($categorySlug)) {
            $this->query->whereHas('category', function (Builder $q) use ($categorySlug): void {
                $q->where('slug', $categorySlug);
            });
        }

        return $this;
    }

    public function inStockOnly(bool $only): self
    {
        if ($only) {
            $this->query->inStock();
        }

        return $this;
    }

    public function priceBetween(?int $min, ?int $max): self
    {
        if ($min !== null) {
            $this->query->where('retail_price', '>=', $min);
        }

        if ($max !== null) {
            $this->query->where('retail_price', '<=', $max);
        }

        return $this;
    }

    public function sort(?string $sort): self
    {
        match ($sort) {
            'price_asc' => $this->query->orderBy('retail_price'),
            'price_desc' => $this->query->orderByDesc('retail_price'),
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
