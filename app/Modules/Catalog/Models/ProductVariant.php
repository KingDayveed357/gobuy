<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\ProductVariantFactory;
use App\Modules\Pricing\Concerns\RecordsPriceHistory;
use App\Modules\Pricing\Models\PromotionalPrice;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    use RecordsPriceHistory;

    /**
     * Optional, non-persisted annotation recorded onto the {@see PriceHistory}
     * row when a priced field changes (e.g. set by the bulk price tool).
     */
    public ?string $priceChangeReason = null;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'retail_price',
        'sale_price',
        'wholesale_price',
        'stock',
        'low_stock_threshold',
        'is_default',
        'sort_order',
    ];

    protected $attributes = [
        'name' => 'Default',
        'stock' => 0,
        'is_default' => false,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'retail_price' => Money::class,
            'sale_price' => Money::class,
            'wholesale_price' => Money::class,
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->low_stock_threshold;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'product_option_value_variant');
    }

    public function promotionalPrices(): HasMany
    {
        return $this->hasMany(PromotionalPrice::class);
    }

    /**
     * The live promotional unit price right now, or null when none applies.
     * Uses an eager-loaded relation when present (avoids N+1 on listings),
     * otherwise queries the cheapest live promo for this variant.
     */
    public function livePromotionalPrice(): ?Money
    {
        if ($this->relationLoaded('promotionalPrices')) {
            $promo = $this->promotionalPrices
                ->filter(fn (PromotionalPrice $p) => $p->isLive())
                ->sortBy(fn (PromotionalPrice $p) => $p->price->kobo)
                ->first();

            return $promo?->price;
        }

        return $this->promotionalPrices()->live()->orderBy('price')->first()?->price;
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Human label, e.g. "Large / Red" (falls back to the variant name).
     */
    public function label(): string
    {
        if ($this->relationLoaded('optionValues') && $this->optionValues->isNotEmpty()) {
            return $this->optionValues->pluck('value')->implode(' / ');
        }

        return $this->name;
    }

    protected static function newFactory(): ProductVariantFactory
    {
        return ProductVariantFactory::new();
    }
}
