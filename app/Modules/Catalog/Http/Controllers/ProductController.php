<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Queries\ProductQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = ProductQuery::make()
            ->active()
            ->search($request->string('q')->toString())
            ->inCategory($request->string('category')->toString())
            ->inStockOnly($request->boolean('in_stock'))
            ->priceBetween(
                $request->filled('min') ? $request->integer('min') : null,
                $request->filled('max') ? $request->integer('max') : null,
            )
            ->sort($request->string('sort')->toString())
            ->paginate();

        $categories = Category::active()->orderBy('sort_order')->get();

        return view('storefront.products.index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function show(Product $product): View
    {
        abort_if($product->status !== 'active', 404);

        $product->load(['category', 'images']);

        $related = Product::active()
            ->whereBelongsTo($product->category)
            ->whereKeyNot($product->id)
            ->with('images')
            ->take(8)
            ->get();

        return view('storefront.products.show', [
            'product' => $product,
            'related' => $related,
        ]);
    }
}
