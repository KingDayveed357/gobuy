<?php

namespace App\Modules\Marketing\Models;

use App\Modules\Pricing\Models\Coupon;
use App\Modules\Pricing\Models\PromotionalPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A marketing campaign — the umbrella that coordinates a landing page, banners,
 * homepage sections, coupons and promotional prices under ONE schedule and one
 * launch/kill switch. Fixes the "four independent schedules" problem.
 */
class Campaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_LIVE = 'live';

    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'name', 'slug', 'status', 'page_id', 'accent_color', 'badge_text', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $campaign): void {
            if (! $campaign->slug && $campaign->name) {
                $campaign->slug = Str::slug($campaign->name).'-'.Str::random(4);
            }
        });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(HomepageSection::class);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function promotionalPrices(): HasMany
    {
        return $this->hasMany(PromotionalPrice::class);
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function isEnded(): bool
    {
        return $this->status === self::STATUS_ENDED;
    }

    public function memberCount(): int
    {
        return $this->sections()->count() + $this->banners()->count()
            + $this->coupons()->count() + $this->promotionalPrices()->count();
    }
}
