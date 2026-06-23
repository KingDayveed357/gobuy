<?php

namespace App\Modules\Marketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): View
    {
        // The reactive Livewire page (wishlist.wishlist-page) fetches + paginates itself.
        return view('account.wishlist');
    }

    /**
     * Add or remove a product from the authenticated user's wishlist.
     */
    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $existing = $user->wishlistItems()->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();
            $wished = false;
        } else {
            $user->wishlistItems()->create(['product_id' => $product->id]);
            $wished = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'wished' => $wished,
                'count' => $user->wishlistItems()->count(),
            ]);
        }

        return back()->with('status', $wished ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
    }

    public function toCart(Product $product): RedirectResponse
    {
        $variant = $product->primaryVariant();

        if (! $variant || ! $product->isInStock()) {
            return back()->with('error', 'This item is out of stock.');
        }

        $this->cart->add($variant, 1);
        Auth::user()->wishlistItems()->where('product_id', $product->id)->delete();

        return redirect()->route('cart.index')->with('status', 'Moved to your cart.');
    }

    /**
     * Merge a set of product ids (a guest's localStorage wishlist) into the
     * authenticated user's wishlist, de-duplicating against what's saved.
     */
    public function merge(Request $request): JsonResponse
    {
        $ids = collect($request->input('product_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique();

        $user = Auth::user();

        if ($ids->isNotEmpty()) {
            // Only merge ids that are real products and not already saved.
            $valid = Product::whereIn('id', $ids)->pluck('id');
            $existing = $user->wishlistItems()->pluck('product_id');

            $valid->diff($existing)->each(
                fn ($id) => $user->wishlistItems()->create(['product_id' => $id])
            );
        }

        $items = $user->wishlistItems()->pluck('product_id');

        return response()->json([
            'count' => $items->count(),
            'product_ids' => $items,
        ]);
    }
}
