<?php

namespace App\Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Banner extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const MEDIA_IMAGE = 'image';

    public const MEDIA_MOBILE = 'mobile';

    /** Colour presets used when a banner has no background image. */
    public const THEMES = [
        'indigo' => 'linear-gradient(120deg, #4f46e5 0%, #7c3aed 52%, #2563eb 100%)',
        'sky' => 'linear-gradient(120deg, #0ea5e9 0%, #38bdf8 100%)',
        'emerald' => 'linear-gradient(120deg, #047857 0%, #10b981 100%)',
        'amber' => 'linear-gradient(120deg, #b45309 0%, #f59e0b 100%)',
        'rose' => 'linear-gradient(120deg, #9f1239 0%, #fb7185 100%)',
        'slate' => 'linear-gradient(120deg, #0f172a 0%, #334155 100%)',
    ];

    public const LAYOUTS = ['hero', 'split', 'grid'];

    protected $fillable = [
        'title', 'subtitle', 'cta_label', 'cta_variant', 'link_url',
        'placement', 'layout', 'theme', 'text_theme', 'overlay_opacity',
        'focal_point', 'is_active', 'sort_order', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'overlay_opacity' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_IMAGE)->singleFile();
        $this->addMediaCollection(self::MEDIA_MOBILE)->singleFile();
    }

    public function imageUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::MEDIA_IMAGE);

        return $url !== '' ? $url : null;
    }

    public function mobileImageUrl(): ?string
    {
        $url = $this->getFirstMediaUrl(self::MEDIA_MOBILE);

        return $url !== '' ? $url : $this->imageUrl();
    }

    public function gradient(): string
    {
        return self::THEMES[$this->theme] ?? self::THEMES['indigo'];
    }

    /** Whether the banner should display now (active + within schedule window). */
    public function isLive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        return ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->ends_at === null || $this->ends_at->gte($now));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Active and within the (optional) scheduling window. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopePlacement(Builder $query, string $placement): Builder
    {
        return $query->where('placement', $placement);
    }
}
