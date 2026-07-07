<?php

namespace App\Modules\Marketing\Services;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\Page;

/**
 * Turns a structured Link ({type, ref, label}) into a live storefront URL.
 *
 * Links store the target's ID, not its URL, so a product/category/brand slug
 * rename never breaks a banner or section. When the target is gone or inactive
 * the link is "broken" — resolve() returns null and callers can flag it.
 *
 * @phpstan-type LinkArray array{type?: string, ref?: string|int|null, label?: string|null}
 */
class LinkResolver
{
    public const TYPES = ['product', 'category', 'brand', 'page', 'search', 'products', 'home', 'url'];

    /**
     * The URL for a structured link, or the legacy raw string when no structured
     * link is set, or null when neither yields a destination.
     *
     * @param  LinkArray|null  $link
     */
    public function urlFor(?array $link, ?string $legacy = null): ?string
    {
        return $this->resolve($link) ?? ($legacy !== '' ? $legacy : null);
    }

    /**
     * @param  LinkArray|null  $link
     */
    public function resolve(?array $link): ?string
    {
        $type = $link['type'] ?? null;
        $ref = isset($link['ref']) ? (string) $link['ref'] : '';

        return match ($type) {
            'product' => ($p = Product::active()->find($ref)) ? route('products.show', $p) : null,
            'category' => ($c = Category::active()->find($ref)) ? route('products.index', ['category' => $c->slug]) : null,
            'brand' => ($b = Brand::where('is_active', true)->find($ref)) ? route('products.index', ['brand' => $b->slug]) : null,
            // Stores the page ID; the URL is generated fresh per request, so it is
            // slug-rename-safe AND environment-safe (never bakes a host into the DB).
            'page' => ($pg = Page::published()->find($ref)) ? $pg->url() : null,
            'search' => $ref !== '' ? route('products.index', ['q' => $ref]) : null,
            'products' => route('products.index'),
            'home' => route('home'),
            'url' => $ref !== '' ? $ref : null,
            default => null,
        };
    }

    /**
     * A structured link exists but its target can no longer be resolved.
     *
     * @param  LinkArray|null  $link
     */
    public function isBroken(?array $link): bool
    {
        return ! empty($link['type']) && $this->resolve($link) === null;
    }

    /**
     * A human label for the link (for admin display), falling back to the type.
     *
     * @param  LinkArray|null  $link
     */
    public function label(?array $link): ?string
    {
        if (empty($link['type'])) {
            return null;
        }

        if (! empty($link['label'])) {
            return $link['label'];
        }

        return match ($link['type']) {
            'products' => 'All products',
            'home' => 'Homepage',
            'search' => 'Search: '.($link['ref'] ?? ''),
            'url' => $link['ref'] ?? 'Custom URL',
            default => ucfirst($link['type']),
        };
    }
}
