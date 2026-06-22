<?php

namespace App\Modules\Cart\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Http\Requests\AddToCartRequest;
use App\Modules\Cart\Http\Requests\UpdateCartItemRequest;
use App\Modules\Cart\Models\CartItem;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Pricing\Services\CouponService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CouponService $coupons,
    ) {}

    public function index(): View
    {
        $summary = $this->cart->summary();
        $coupon = $this->coupons->resolveForCart($summary, auth()->user());

        return view('storefront.cart', [
            ...$summary,
            'appliedCoupon' => $coupon['coupon'] ?? null,
            'discount' => $coupon['discount'] ?? Money::zero(),
            'total' => $summary['subtotal']->minus($coupon['discount'] ?? Money::zero()),
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $variant = ProductVariant::with('product')->findOrFail($request->integer('product_variant_id'));

        abort_unless($variant->product && $variant->product->status === 'active', 404);

        $this->cart->add($variant, $request->integer('quantity', 1));

        return redirect()
            ->route('cart.index')
            ->with('status', "{$variant->product->name} added to cart.");
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

    public function setQuantity(Request $request): RedirectResponse
    {
        $request->validate([
            'product_variant_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $variant = ProductVariant::with('product')->findOrFail($request->integer('product_variant_id'));
        abort_unless($variant->product && $variant->product->status === 'active', 404);

        $cart = $this->cart->getOrCreate();
        $item = $cart->items()->firstWhere('product_variant_id', $variant->id);

        if ($item) {
            $this->cart->updateQuantity($item, $request->integer('quantity'));
        } else {
            $this->cart->add($variant, $request->integer('quantity'));
        }

        return redirect()->back()->with('status', 'Cart updated.');
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
