<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\Response;

/**
 * Search-engine endpoints: an XML sitemap of crawlable URLs and a robots.txt
 * that points back to it. Both are generated from live catalog data.
 */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = [];

        $urls[] = ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'];
        $urls[] = ['loc' => route('products.index'), 'changefreq' => 'daily', 'priority' => '0.9'];

        Category::active()->get()->each(function (Category $category) use (&$urls): void {
            $urls[] = [
                'loc' => route('products.index', ['category' => $category->slug]),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        });

        Product::active()->select(['slug', 'updated_at'])->get()->each(function (Product $product) use (&$urls): void {
            $urls[] = [
                'loc' => route('products.show', $product->slug),
                'lastmod' => $product->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        });

        $xml = view('storefront.seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /account',
            'Disallow: /admin',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }
}
