<?php

namespace App\Modules\Marketing\Models;

use App\Modules\Marketing\Enums\SectionSource;
use App\Modules\Marketing\Enums\SectionStatus;
use App\Modules\Marketing\Enums\SectionType;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use App\Modules\Marketing\Services\LinkResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A configurable homepage merchandising block. The marketing team composes the
 * storefront by ordering/scheduling these (product rails, category grids, brand
 * rails, banner rows) — no developer required. Content is resolved at render
 * time by {@see HomepageMerchandiser}.
 */
class HomepageSection extends Model
{
    protected $fillable = [
        'placement', 'type', 'source', 'source_ref', 'title', 'subtitle',
        'cta_label', 'cta_url', 'cta_link', 'item_limit', 'settings', 'is_active',
        'status', 'sort_order', 'starts_at', 'ends_at', 'campaign_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
            'source' => SectionSource::class,
            'status' => SectionStatus::class,
            'settings' => 'array',
            'cta_link' => 'array',
            'item_limit' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** A single value from the `settings` JSON bag (editorial copy, media, etc.). */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * The ordered banner ids a banner-row block directly references.
     *
     * @return list<int>
     */
    public function bannerIds(): array
    {
        return collect($this->setting('banner_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    /** Resolved "See all" URL — structured Link first, legacy cta_url fallback. */
    public function destinationUrl(): ?string
    {
        return app(LinkResolver::class)->urlFor($this->cta_link, $this->cta_url);
    }

    public function hasBrokenLink(): bool
    {
        return app(LinkResolver::class)->isBroken($this->cta_link);
    }

    /** Publicly live: published, active, and within the (optional) schedule window. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', SectionStatus::Published->value)
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order');
    }

    /**
     * The full intended composition for the preview canvas — every active section
     * (drafts + published), ignoring the schedule window so staged and upcoming
     * work can be reviewed together before publishing.
     */
    public function scopePreviewable(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function isDraft(): bool
    {
        return $this->status === SectionStatus::Draft;
    }

    public function scopeForPlacement(Builder $query, string $placement): Builder
    {
        return $query->where('placement', $placement);
    }

    public function isLive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        return ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->ends_at === null || $this->ends_at->gte($now));
    }
}
