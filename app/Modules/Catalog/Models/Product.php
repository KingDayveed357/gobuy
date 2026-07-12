<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Database\Factories\ProductFactory;
use App\Modules\Pricing\Models\QuantityDiscount;
use App\Modules\Review\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use SoftDeletes;

    public const MEDIA_COLLECTION = 'gallery';

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'condition',
        'weight_g',
        'length_mm',
        'width_mm',
        'height_mm',
        'cost_price_usd',
        'is_vat_inclusive',
        'is_tax_exempt',
        'vat_rate',
        'status',
        'is_returnable',
        'return_window_days',
        'is_featured',
        'rating_avg',
        'rating_count',
    ];

    protected $attributes = [
        'status' => 'draft',
        'is_featured' => false,
        'condition' => 'new',
        'is_vat_inclusive' => true,
        'is_tax_exempt' => false,
        'vat_rate' => 7.5,
    ];

    protected function casts(): array
    {
        return [
            'is_vat_inclusive' => 'boolean',
            'is_tax_exempt' => 'boolean',
            'vat_rate' => 'decimal:2',
            'is_returnable' => 'boolean',
            'return_window_days' => 'integer',
            'is_featured' => 'boolean',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
            'weight_g' => 'integer',
            'length_mm' => 'integer',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
            'cost_price_usd' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION);
    }

    /**
     * Auto-optimised derivatives generated on upload (and for the bulk image
     * importer): a square gallery thumbnail and a resized web image, both
     * compressed WebP with EXIF orientation applied by the driver. Non-queued so
     * they exist immediately without depending on a running queue worker.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 400, 400)
            ->format('webp')
            ->quality(80)
            ->nonQueued();

        $this->addMediaConversion('web')
            ->fit(Fit::Max, 1400, 1400)
            ->format('webp')
            ->quality(82)
            ->nonQueued();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function quantityDiscounts(): HasMany
    {
        return $this->hasMany(QuantityDiscount::class)->orderBy('min_qty');
    }

    /**
     * The variant a price/stock decision defaults to (the flagged default,
     * else the first). Works whether or not relations are eager-loaded.
     */
    public function primaryVariant(): ?ProductVariant
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->firstWhere('is_default', true) ?? $this->variants->first();
        }

        return $this->defaultVariant()->first() ?? $this->variants()->first();
    }

    public function hasVariants(): bool
    {
        return $this->variants()->where('is_default', false)->exists();
    }

    public function getStockAttribute(): int
    {
        return $this->relationLoaded('variants')
            ? (int) $this->variants->sum('stock')
            : (int) $this->variants()->sum('stock');
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * The primary image, preferring the optimised WebP conversion and falling
     * back to the original when a conversion hasn't been generated (e.g. legacy
     * uploads or SVG placeholders, which aren't rasterised).
     */
    public function imageUrl(string $conversion = 'web'): string
    {
        $media = $this->getFirstMedia(self::MEDIA_COLLECTION);

        if (! $media) {
            return asset('theme/img/placeholder.svg');
        }

        return $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }

    /**
     * Square thumbnail URL for lists and cards (optimised WebP, original as fallback).
     */
    public function thumbUrl(): string
    {
        return $this->imageUrl('thumb');
    }

    /**
     * Every gallery image URL, falling back to a single placeholder when the
     * product has no media yet.
     *
     * @return list<string>
     */
    public function imageUrls(): array
    {
        $urls = $this->getMedia(self::MEDIA_COLLECTION)
            ->map(fn ($media) => $media->getUrl())
            ->all();

        return $urls !== [] ? $urls : [asset('theme/img/placeholder.svg')];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('variants', fn (Builder $q) => $q->where('stock', '>', 0));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
