<?php

namespace App\Modules\Marketing\Support;

use App\Modules\Marketing\Models\HomepageSection;
use Illuminate\Support\Collection;

/**
 * A homepage section paired with its resolved content (products, categories,
 * brands, or banners), ready for the view. Keeps rendering logic out of the
 * controller and content queries out of Blade.
 */
final class ResolvedSection
{
    public function __construct(
        public readonly HomepageSection $section,
        public readonly Collection $items,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }
}
