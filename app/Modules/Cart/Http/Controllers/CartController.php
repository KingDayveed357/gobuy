<?php

namespace App\Modules\Cart\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Http\Requests\AddToCartRequest;
use App\Modules\Cart\Http\Requests\UpdateCartItemRequest;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): View
    {
        return view('storefront.cart', $this->cart->summary());
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $product = Product::active()->findOrFail($request->integer('product_id'));

        $this->cart->add($product, $request->integer('quantity', 1));

        return redirect()
            ->route('cart.index')
            ->with('status', "{$product->name} added to cart.");
    }

    public function update(UpdateCartItemRequest $request, CartItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        $this->cart->updateQuantity($item, $request->integer('quantity'));

        return redirect()->route('cart.index')->with('status', 'Cart updated.');
    }

    public function destroy(CartItem $item): RedirectResponse
    {
        $this->authorizeItem($item);

        $this->cart->remove($item);

        return redirect()->route('cart.index')->with('status', 'Item removed.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return redirect()->route('cart.index')->with('status', 'Cart cleared.');
    }

    /**
     * Guard against editing an item that does not belong to the current cart.
     */
    private function authorizeItem(CartItem $item): void
    {
        $cart = $this->cart->find();

        abort_if($cart === null || $item->cart_id !== $cart->id, 403);
    }
}
