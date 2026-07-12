<?php

namespace App\Modules\Operations\Packaging\Models;

use App\Modules\Catalog\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A sellable multiple of a variant's base unit — a carton of 12, a pack of 24.
 * Stock never moves in packaging units: {@see baseUnits()} converts a pack count
 * back to the base units the ledger actually deducts.
 */
class PackagingUnit extends Model
{
    protected $fillable = ['product_variant_id', 'name', 'multiplier', 'barcode', 'sku', 'retail_price', 'is_active'];

    protected function casts(): array
    {
        return [
            'multiplier' => 'integer',
            'retail_price' => Money::class,
            'is_active' => 'boolean',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Base units represented by a number of these packaging units. */
    public function baseUnits(int $packs): int
    {
        return max(0, $packs) * $this->multiplier;
    }

    /**
     * The price of one packaging unit — its own price when set, otherwise derived
     * from the variant's base retail price × the multiplier.
     */
    public function unitPrice(): Money
    {
        if ($this->retail_price instanceof Money && ! $this->retail_price->isZero()) {
            return $this->retail_price;
        }

        $base = $this->variant?->retail_price;

        return $base instanceof Money ? $base->times($this->multiplier) : Money::zero();
    }
}
