<?php

namespace App\Modules\Marketing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A merchandisable storefront page. Its sections associate by `placement = slug`
 * and render through the same {@see HomepageMerchandiser} engine as the homepage.
 * The 'home' page is seeded and rendered at "/"; everything else lives at
 * "/p/{slug}".
 */
class Page extends Model
{
    public const HOME = 'home';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = ['slug', 'title', 'meta_title', 'meta_description', 'status'];

    protected static function booted(): void
    {
        static::saving(function (self $page): void {
            if (! $page->slug && $page->title) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    /** Sections associate by placement = slug (string link, not a foreign key). */
    public function sections(): HasMany
    {
        return $this->hasMany(HomepageSection::class, 'placement', 'slug');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isHome(): bool
    {
        return $this->slug === self::HOME;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /** The public storefront URL for this page. */
    public function url(): string
    {
        return $this->isHome() ? route('home') : route('storefront.page', $this->slug);
    }
}
