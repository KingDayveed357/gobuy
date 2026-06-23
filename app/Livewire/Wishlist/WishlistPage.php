<?php

namespace App\Livewire\Wishlist;

use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Authenticated wishlist page. Reactive remove + move-to-cart with **no reload**,
 * keeps the 9-per-page pagination, and dispatches `wishlist-updated` so the
 * navbar badge stays in sync from the same single source of truth.
 */
class WishlistPage extends Component
{
    use WithPagination;

    public function remove(int $productId): void
    {
        $user = Auth::user();
        $user->wishlistItems()->where('product_id', $productId)->delete();

        $this->dispatch('wishlist-updated', count: $user->wishlistItems()->count());
        $this->dispatch('toast', type: 'info', message: 'Removed from your wishlist.');
    }

    public function moveToCart(int $productId, CartService $cart): void
    {
        $product = Product::with('variants')->find($productId);
        $variant = $product?->primaryVariant();

        if (! $variant || ! $product->isInStock()) {
            $this->dispatch('toast', type: 'error', message: 'This item is out of stock.');

            return;
        }

        $cart->add($variant, 1);
        Auth::user()->wishlistItems()->where('product_id', $productId)->delete();

        $this->dispatch('cart-updated');
        $this->dispatch('wishlist-updated', count: Auth::user()->wishlistItems()->count());
        $this->dispatch('toast', type: 'success', message: 'Moved to your cart.');
    }

    public function render()
    {
        $items = Auth::user()->wishlistItems()
            ->whereHas('product')
            ->with(['product.variants.promotionalPrices', 'product.media', 'product.category'])
            ->paginate(9);

        return view('livewire.wishlist.wishlist-page', ['items' => $items]);
    }
}
