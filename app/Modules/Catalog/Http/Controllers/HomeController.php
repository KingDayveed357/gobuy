<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\Banner;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Product::active()
            ->featured()
            ->with(['variants', 'quantityDiscounts', 'media'])
            ->take(8)
            ->get();

        $latest = Product::active()
            ->with(['variants', 'quantityDiscounts', 'media'])
            ->latest()
            ->take(8)
            ->get();

        // Only top-level categories belong in the "Shop by category" strip.
        $categories = Category::active()->roots()->orderBy('sort_order')->orderBy('name')->get();

        $heroBanners = Banner::live()->placement('home_hero')->orderBy('sort_order')->get();

        return view('storefront.home', [
            'featured' => $featured,
            'latest' => $latest,
            'categories' => $categories,
            'heroBanners' => $heroBanners,
        ]);
    }
}
