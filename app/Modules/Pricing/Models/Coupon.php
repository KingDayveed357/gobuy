<?php

namespace App\Modules\Pricing\Models;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_cart_value',
        'is_active',
        'eligibility',
        'starts_at',
        'expires_at',
        'usage_limit_total',
        'usage_limit_per_user',
        'campaign_id',
    ];

    protected $attributes = [
        'is_active' => false,
        'eligibility' => 'both',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_cart_value' => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'usage_limit_total' => 'integer',
            'usage_limit_per_user' => 'integer',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    protected static function newFactory(): CouponFactory
    {
        return CouponFactory::new();
    }
}
