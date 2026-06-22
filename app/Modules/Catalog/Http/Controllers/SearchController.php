<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\SearchTerm;
use App\Modules\Pricing\Services\PriceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private readonly PriceResolver $prices) {}

    /**
     * Autocomplete + trending suggestions for the predictive search box.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $q = trim($request->string('q')->toString());
        $user = $request->user();

        $products = [];
        $terms = [];

        if (mb_strlen($q) >= 2) {
            $matches = Product::active()
                ->where('name', 'like', '%'.$q.'%')
                ->with(['media', 'variants', 'quantityDiscounts'])
                ->limit(6)
                ->get();

            $products = $matches->map(fn (Product $p) => [
                'name' => $p->name,
                'url' => route('products.show', $p),
                'image' => $p->imageUrl(),
                'price' => money($this->prices->for($p, $user, 1)->unitPrice),
                'category' => $p->category?->name,
            ])->all();

            // De-duplicated name fragments for the text-suggestion list.
            $terms = $matches->pluck('name')->take(5)->values()->all();
        }

        return response()->json([
            'query' => $q,
            'products' => $products,
            'terms' => $terms,
            'trending' => SearchTerm::trending()->all(),
        ]);
    }
}
