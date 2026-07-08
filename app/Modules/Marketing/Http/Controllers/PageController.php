<?php

namespace App\Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\Page;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use App\Modules\Marketing\Support\ResolvedSection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Renders a published landing page (/p/{slug}) from its merchandising sections —
 * the same engine as the homepage, so campaigns get premium destinations instead
 * of the generic filtered product grid.
 */
class PageController extends Controller
{
    public function show(string $slug, HomepageMerchandiser $merchandiser): View
    {
        abort_if($slug === Page::HOME, 404); // the homepage lives at "/"

        $page = Page::published()->with('campaign')->where('slug', $slug)->firstOrFail();
        $sections = $merchandiser->resolveFor($page->slug);

        return view('storefront.page', [
            'page' => $page,
            'campaign' => $page->campaign,
            'sections' => $sections,
            // Social share image — the campaign's own creative if it has one,
            // else the first banner/product image on the page.
            'ogImage' => $this->deriveOgImage($sections),
        ]);
    }

    /**
     * The best image to represent this page in a social share card: the first
     * banner creative on the page (a campaign's hero artwork), else the first
     * product image, else an editorial image. Absolute URL, or null.
     *
     * @param  Collection<int, ResolvedSection>  $sections
     */
    private function deriveOgImage(Collection $sections): ?string
    {
        // Prefer a real banner creative — that is the campaign's artwork.
        foreach ($sections as $resolved) {
            foreach ($resolved->items as $item) {
                if ($item instanceof Banner && $item->imageUrl()) {
                    return $this->absolute($item->imageUrl());
                }
            }
        }

        // Then any product image, then an editorial image.
        foreach ($sections as $resolved) {
            foreach ($resolved->items as $item) {
                if ($item instanceof Product && $item->imageUrl()) {
                    return $this->absolute($item->imageUrl());
                }
            }

            if ($image = $resolved->section->setting('image_url')) {
                return $this->absolute($image);
            }
        }

        return null;
    }

    /** Social crawlers need an absolute og:image URL (APP_URL per environment). */
    private function absolute(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : url($url);
    }
}
