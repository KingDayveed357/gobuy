<?php

namespace App\Modules\Marketing\Models;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A hand-curated list of products the marketing team can merchandise as a
 * homepage section (source = manual). Named ProductCollection to avoid clashing
 * with Illuminate\Support\Collection.
 */
class ProductCollection extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $collection): void {
            if (! $collection->slug) {
                $collection->slug = Str::slug($collection->name).'-'.Str::random(5);
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products', 'collection_id', 'product_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
