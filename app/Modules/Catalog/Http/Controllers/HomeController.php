<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Product::active()
            ->featured()
            ->with('images')
            ->take(8)
            ->get();

        $latest = Product::active()
            ->with('images')
            ->latest()
            ->take(8)
            ->get();

        $categories = Category::active()->orderBy('sort_order')->get();

        return view('storefront.home', [
            'featured' => $featured,
            'latest' => $latest,
            'categories' => $categories,
        ]);
    }
}
