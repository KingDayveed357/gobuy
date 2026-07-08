<?php

namespace App\Modules\Marketing\Services;

use App\Modules\Marketing\Enums\SectionSource;
use App\Modules\Marketing\Enums\SectionType;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\HomepageSection;

/**
 * Decides whether a merchandising block is complete enough to go live. A block
 * that would render nothing — a rail with no source, a manual collection with no
 * products, an editorial block with no copy, a banner row with no live banners —
 * must never be published. Returns human, contextual problems for the admin so
 * the reason is clear before they hit publish.
 */
class SectionValidator
{
    public function __construct(private readonly HomepageMerchandiser $merchandiser) {}

    /**
     * Reasons this block cannot go live. Empty array = publishable.
     *
     * @return list<string>
     */
    public function problems(HomepageSection $section): array
    {
        return match (true) {
            $section->type->isEditorial() => $this->editorialProblems($section),
            $section->type === SectionType::BannerRow => $this->bannerRowProblems($section),
            default => $this->contentProblems($section),
        };
    }

    public function isPublishable(HomepageSection $section): bool
    {
        return $this->problems($section) === [];
    }

    /**
     * @return list<string>
     */
    private function editorialProblems(HomepageSection $section): array
    {
        $problems = [];

        if (blank($section->title) && blank($section->setting('body'))) {
            $problems[] = 'Add a heading or body copy — this editorial block has no content.';
        }

        if ($section->type === SectionType::EditorialMedia && blank($section->setting('image_url'))) {
            $problems[] = 'Add an image for this image-and-copy block.';
        }

        return $problems;
    }

    /**
     * @return list<string>
     */
    private function bannerRowProblems(HomepageSection $section): array
    {
        $ids = $section->bannerIds();

        if ($ids !== []) {
            if (Banner::query()->live()->whereIn('id', $ids)->doesntExist()) {
                return ['None of the chosen banners are live right now — pick or activate a banner.'];
            }

            return [];
        }

        // Legacy rows still resolve from a placement bucket.
        $placement = $section->source_ref ?: 'home_hero';
        if (Banner::query()->placement($placement)->live()->doesntExist()) {
            return ['No banners chosen yet — add at least one banner to this row.'];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function contentProblems(HomepageSection $section): array
    {
        if ($section->type->usesProductSource()) {
            if (! $section->source instanceof SectionSource) {
                return ['Choose where the products come from.'];
            }

            if ($section->source->needsRef() && blank($section->source_ref)) {
                return ['Pick a '.$this->refNoun($section->source).' for this block.'];
            }
        }

        if ($this->merchandiser->resolveSection($section)->isEmpty()) {
            return ['This block resolves to no items right now — try a different source or add items to it.'];
        }

        return [];
    }

    private function refNoun(SectionSource $source): string
    {
        return match ($source) {
            SectionSource::Category => 'category',
            SectionSource::Brand => 'brand',
            SectionSource::Manual => 'collection',
            default => 'source',
        };
    }
}
