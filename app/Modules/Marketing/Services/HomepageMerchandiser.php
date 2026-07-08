<?php

namespace App\Modules\Marketing\Services;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Enums\SectionSource;
use App\Modules\Marketing\Enums\SectionStatus;
use App\Modules\Marketing\Enums\SectionType;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Models\ProductCollection;
use App\Modules\Marketing\Support\ResolvedSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Turns the admin-configured {@see HomepageSection} list into render-ready
 * {@see ResolvedSection}s. When nothing is configured it falls back to a sensible
 * default composition, so the storefront is never empty and works out of the box.
 */
class HomepageMerchandiser
{
    /**
     * @return Collection<int, ResolvedSection>
     */
    public function resolveFor(string $placement = 'home'): Collection
    {
        $ttl = (int) config('gobuy.homepage_cache_ttl', 300);

        // Only the high-traffic homepage is cached; landing pages resolve fresh,
        // which keeps cache invalidation to the single 'home' key.
        if ($ttl <= 0 || $placement !== 'home') {
            return $this->build($placement);
        }

        return Cache::remember(self::cacheKey($placement), $ttl, fn () => $this->build($placement));
    }

    /**
     * The full intended composition (drafts + published), never cached — powers
     * the signed preview URL so marketers can review before publishing.
     *
     * @return Collection<int, ResolvedSection>
     */
    public function resolveForPreview(string $placement = 'home'): Collection
    {
        return $this->build($placement, preview: true);
    }

    public static function cacheKey(string $placement = 'home'): string
    {
        return 'homepage_sections:'.$placement;
    }

    /** Drop the cached homepage so the next request rebuilds it. */
    public static function forget(string $placement = 'home'): void
    {
        Cache::forget(self::cacheKey($placement));
    }

    /**
     * @return Collection<int, ResolvedSection>
     */
    private function build(string $placement, bool $preview = false): Collection
    {
        $query = HomepageSection::query()->forPlacement($placement);
        $sections = ($preview ? $query->previewable() : $query->live())->get();

        if ($sections->isEmpty()) {
            $sections = $this->defaultSections();
        }

        return $sections
            ->map(fn (HomepageSection $section) => new ResolvedSection($section, $this->itemsFor($section)))
            // Drop content blocks that resolved to nothing, but keep editorial
            // (text/media) blocks — they carry their own copy and have no items.
            ->reject(fn (ResolvedSection $resolved) => $resolved->isEmpty() && ! $resolved->section->type->isEditorial())
            ->values();
    }

    /**
     * Resolve a single section (including empty ones) — used by the admin builder
     * canvas to show a live mini-preview of each block.
     */
    public function resolveSection(HomepageSection $section): ResolvedSection
    {
        return new ResolvedSection($section, $this->itemsFor($section));
    }

    /**
     * Resolve the content collection for a single section based on its type + source.
     */
    private function itemsFor(HomepageSection $section): Collection
    {
        return match ($section->type) {
            SectionType::BannerRow => $this->banners($section),
            SectionType::CategoryGrid => $this->categories($section),
            SectionType::BrandRail => Brand::query()->where('is_active', true)
                ->orderBy('name')->take($section->item_limit)->get(),
            SectionType::ProductRail, SectionType::ProductGrid, SectionType::CountdownDeal => $this->products($section),
            // Editorial blocks render from their own `settings`, not a collection.
            SectionType::RichText, SectionType::EditorialMedia => collect(),
        };
    }

    /**
     * Banners for a banner-row block. Prefers the block's own ordered list of
     * specific banners (settings.banner_ids); falls back to the legacy placement
     * bucket for rows configured before direct references existed.
     */
    private function banners(HomepageSection $section): Collection
    {
        $ids = $section->bannerIds();

        if ($ids !== []) {
            $live = Banner::query()->live()->whereIn('id', $ids)->get()->keyBy('id');

            // Preserve the admin-chosen order; drop any that are no longer live.
            return collect($ids)->map(fn (int $id) => $live->get($id))->filter()->values();
        }

        return Banner::query()->live()
            ->placement($section->source_ref ?: 'home_hero')
            ->orderBy('sort_order')->get();
    }

    private function categories(HomepageSection $section): Collection
    {
        $query = Category::query()->active()->orderBy('sort_order')->orderBy('name');

        // A source_ref narrows to the children of that parent category.
        $query = $section->source_ref
            ? $query->where('parent_id', (int) $section->source_ref)
            : $query->roots();

        $categories = $query->take($section->item_limit)->get();
        $this->attachRepresentativeImages($categories);

        return $categories;
    }

    /**
     * Attach a `representative_image` to each category — the newest product's
     * image — so tiles feel like a real marketplace. Two queries total (bounded
     * by the number of tiles), and the whole result is cached.
     *
     * @param  Collection<int, Category>  $categories
     */
    private function attachRepresentativeImages(Collection $categories): void
    {
        if ($categories->isEmpty()) {
            return;
        }

        $representativeIds = Product::query()->active()
            ->whereIn('category_id', $categories->pluck('id'))
            ->selectRaw('MAX(id) as id')
            ->groupBy('category_id')
            ->pluck('id');

        $products = Product::query()->whereIn('id', $representativeIds)->with('media')->get()->keyBy('category_id');

        $categories->each(function (Category $category) use ($products): void {
            $url = $products->get($category->id)?->getFirstMediaUrl(Product::MEDIA_COLLECTION);
            $category->setAttribute('representative_image', $url !== '' ? $url : null);
        });
    }

    private function products(HomepageSection $section): Collection
    {
        if ($section->source === SectionSource::Manual) {
            return $this->collectionProducts($section);
        }

        $query = Product::query()->active()
            ->with(['variants.promotionalPrices', 'quantityDiscounts', 'media']);

        $query = match ($section->source) {
            SectionSource::Featured => $query->featured()->latest(),
            SectionSource::BestSellers => $query->orderByRaw($this->soldQuantitySubquery().' desc'),
            SectionSource::OnSale => $query->whereHas('variants.promotionalPrices', fn ($q) => $q->live())->latest(),
            SectionSource::Category => $query->where('category_id', (int) $section->source_ref)->latest(),
            SectionSource::Brand => $query->where('brand_id', (int) $section->source_ref)->latest(),
            default => $query->latest(), // Latest / null
        };

        return $query->take($section->item_limit)->get();
    }

    /**
     * Products of a curated collection, in the admin-defined order.
     */
    private function collectionProducts(HomepageSection $section): Collection
    {
        $collection = ProductCollection::query()
            ->where('is_active', true)
            ->with(['products' => fn ($q) => $q->active()->with(['variants.promotionalPrices', 'quantityDiscounts', 'media'])])
            ->find((int) $section->source_ref);

        return $collection ? $collection->products->take($section->item_limit) : collect();
    }

    /**
     * Correlated subquery: total paid units sold per product, for best-seller ranking.
     */
    private function soldQuantitySubquery(): string
    {
        return '(select coalesce(sum(oi.quantity), 0) from order_items oi '
            .'inner join product_variants pv on pv.id = oi.product_variant_id '
            .'inner join orders o on o.id = oi.order_id '
            ."where pv.product_id = products.id and o.payment_status = 'paid')";
    }

    /**
     * The built-in homepage when no sections are configured: category grid,
     * hero banners, a featured rail, and a new-arrivals grid.
     *
     * @return Collection<int, HomepageSection>
     */
    private function defaultSections(): Collection
    {
        $make = function (array $attributes): HomepageSection {
            $section = new HomepageSection($attributes + ['status' => SectionStatus::Published]);
            $section->exists = true; // mark as read-only default (never persisted)

            return $section;
        };

        return collect([
            $make(['type' => SectionType::CategoryGrid, 'title' => 'Shop by category', 'item_limit' => 12,
                'cta_label' => 'View all', 'cta_url' => route('products.index')]),
            $make(['type' => SectionType::BannerRow, 'source_ref' => 'home_hero', 'item_limit' => 6]),
            $make(['type' => SectionType::ProductRail, 'source' => SectionSource::Featured,
                'title' => 'Top deals today', 'item_limit' => 8, 'cta_label' => 'Explore more', 'cta_url' => route('products.index')]),
            $make(['type' => SectionType::ProductGrid, 'source' => SectionSource::Latest,
                'title' => 'New arrivals', 'item_limit' => 12, 'cta_label' => 'Explore more', 'cta_url' => route('products.index')]),
        ]);
    }
}
