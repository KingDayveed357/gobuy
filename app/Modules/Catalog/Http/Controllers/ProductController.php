<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\SearchTerm;
use App\Modules\Catalog\Queries\ProductQuery;
use App\Modules\Pricing\Services\PricingEngine;
use App\Modules\Review\Services\ReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(Request $request): View
    {
        // Record the search to power "trending" suggestions.
        if ($term = $request->string('q')->toString()) {
            SearchTerm::record($term);
        }

        $products = ProductQuery::make()
            ->active()
            ->search($request->string('q')->toString())
            ->inCategory($request->string('category')->toString())
            ->inBrand($request->string('brand')->toString())
            ->inStockOnly($request->boolean('in_stock'))
            ->priceBetween(
                // Filter inputs are Naira; the column is kobo.
                $request->filled('min') ? $request->integer('min') * 100 : null,
                $request->filled('max') ? $request->integer('max') * 100 : null,
            )
            ->sort($request->string('sort')->toString())
            ->paginate();

        $categories = Category::active()->orderBy('sort_order')->get();
        $brands = Brand::where('is_active', true)->whereHas('products', fn ($q) => $q->where('status', 'active'))->orderBy('name')->get();

        return view('storefront.products.index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'activeFilters' => $this->activeFilters($request, $categories, $brands),
        ]);
    }

    /**
     * Human-readable chips for the filters currently applied, each with a URL
     * that removes just that one filter.
     *
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, Brand>  $brands
     * @return array<int, array{label: string, remove_url: string}>
     */
    private function activeFilters(Request $request, $categories, $brands): array
    {
        $chips = [];
        $without = fn (string $key) => route('products.index', array_merge($request->except([$key, 'page']), []));

        if ($term = $request->string('q')->toString()) {
            $chips[] = ['label' => "Search: \"{$term}\"", 'remove_url' => $without('q')];
        }
        if ($slug = $request->string('category')->toString()) {
            $name = $categories->firstWhere('slug', $slug)?->name ?? $slug;
            $chips[] = ['label' => $name, 'remove_url' => $without('category')];
        }
        if ($slug = $request->string('brand')->toString()) {
            $name = $brands->firstWhere('slug', $slug)?->name ?? $slug;
            $chips[] = ['label' => $name, 'remove_url' => $without('brand')];
        }
        if ($request->boolean('in_stock')) {
            $chips[] = ['label' => 'In stock', 'remove_url' => $without('in_stock')];
        }
        if ($request->filled('min') || $request->filled('max')) {
            $min = $request->filled('min') ? '₦'.number_format((int) $request->integer('min')) : '₦0';
            $max = $request->filled('max') ? '₦'.number_format((int) $request->integer('max')) : 'any';
            $chips[] = ['label' => "{$min} – {$max}", 'remove_url' => route('products.index', $request->except(['min', 'max', 'page']))];
        }

        return $chips;
    }

    public function show(Request $request, Product $product, PricingEngine $engine): View
    {
        abort_if($product->status !== 'active', 404);

        $product->load(['category', 'brand', 'variants.optionValues', 'options.values', 'specifications', 'quantityDiscounts', 'media']);

        $related = Product::active()
            ->whereBelongsTo($product->category)
            ->whereKeyNot($product->id)
            ->with(['variants', 'quantityDiscounts', 'media'])
            ->take(8)
            ->get();

        $recentlyViewed = $this->trackRecentlyViewed($request, $product);

        $cartItemsByVariant = $this->cart->find()?->items
            ->mapWithKeys(fn ($item) => [
                $item->product_variant_id => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'updateUrl' => route('cart.items.update', $item),
                ],
            ]) ?? collect();

        $user = auth()->user();
        $variantData = $product->variants->mapWithKeys(function ($v) use ($engine, $user, $cartItemsByVariant) {
            $price = $engine->priceForVariant($v, $user, 1);

            return [$v->id => [
                'label' => $v->label(),
                'sku' => $v->sku,
                'stock' => $v->stock,
                'unit' => $price->unitPrice,
                'retail' => $price->retailPrice,
                'hasDiscount' => $price->hasDiscount(),
                'cartQty' => $cartItemsByVariant->has($v->id) ? $cartItemsByVariant->get($v->id)['quantity'] : 0,
            ]];
        });

        $reviewService = app(ReviewService::class);

        return view('storefront.products.show', [
            'product' => $product,
            'related' => $related,
            'recentlyViewed' => $recentlyViewed,
            'cartItemsByVariant' => $cartItemsByVariant,
            'variantData' => $variantData,
            'reviews' => $product->reviews()->approved()->with('user')->latest()->take(20)->get(),
            'canReview' => $user ? $reviewService->canReview($user, $product) : false,
        ]);
    }

    /**
     * Load the shopper's other recently-viewed products (from a cookie) and
     * queue the current product onto the front of that list.
     *
     * @return Collection<int, Product>
     */
    private function trackRecentlyViewed(Request $request, Product $product): Collection
    {
        $ids = collect(explode(',', (string) $request->cookie('recently_viewed', '')))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $others = $ids->reject(fn ($id) => $id === $product->id);

        $recent = Product::active()
            ->whereIn('id', $others)
            ->with(['variants', 'quantityDiscounts', 'media', 'category'])
            ->get()
            ->sortBy(fn (Product $p) => $others->search($p->id))
            ->take(6)
            ->values();

        $updated = $ids->prepend($product->id)->unique()->take(8)->implode(',');
        cookie()->queue(cookie('recently_viewed', $updated, 60 * 24 * 30));

        return $recent;
    }

    public function wishlist(): View
    {
        // Authenticated users get their persisted wishlist; guests get the
        // localStorage-driven page that hydrates client-side.
        if ($user = auth()->user()) {
            $items = $user->wishlistItems()
                ->whereHas('product')
                ->with(['product.variants', 'product.media', 'product.category'])
                ->paginate(9);

            return view('account.wishlist', ['items' => $items]);
        }

        return view('storefront.wishlist');
    }

    public function wishlistItems(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['html' => '']);
        }

        $products = Product::whereIn('id', $ids)->with(['media', 'variants'])->get();
        $html = view('storefront.wishlist-items', compact('products'))->render();

        return response()->json(['html' => $html]);
    }
}
