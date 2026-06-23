<?php

namespace App\Livewire\Cart;

use App\Modules\Cart\Models\CartItem;
use App\Modules\Cart\Services\CartService;
use App\Modules\Pricing\Services\CouponService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Reactive cart page. All mutations delegate to the shared {@see CartService}
 * and {@see CouponService} — the exact same domain layer the POST controllers
 * use — so there is zero duplicated business logic.
 */
class CartManager extends Component
{
    public string $couponCode = '';

    public function increment(int $itemId, CartService $cart): void
    {
        $this->changeQuantity($itemId, 1, $cart);
    }

    public function decrement(int $itemId, CartService $cart): void
    {
        $this->changeQuantity($itemId, -1, $cart);
    }

    public function setQuantity(int $itemId, int $quantity, CartService $cart): void
    {
        if (($item = $this->ownedItem($itemId, $cart)) !== null) {
            $cart->updateQuantity($item, max(1, $quantity));
            $this->dispatch('cart-updated');
        }
    }

    public function remove(int $itemId, CartService $cart): void
    {
        if (($item = $this->ownedItem($itemId, $cart)) !== null) {
            $cart->remove($item);
            $this->dispatch('cart-updated');
            $this->dispatch('toast', type: 'info', message: 'Item removed.');
        }
    }

    public function clear(CartService $cart): void
    {
        $cart->clear();
        $this->dispatch('cart-updated');
        $this->dispatch('toast', type: 'info', message: 'Cart cleared.');
    }

    public function applyCoupon(CartService $cart, CouponService $coupons): void
    {
        $summary = $cart->summary();
        $result = $coupons->evaluate($this->couponCode, $summary['subtotal'], auth()->user(), new Collection($summary['lines']));

        if (! $result['ok']) {
            $this->dispatch('toast', type: 'error', message: $result['reason']);

            return;
        }

        session([CouponService::SESSION_KEY => $result['coupon']->code]);
        $this->couponCode = '';
        $this->dispatch('toast', type: 'success', message: "Code {$result['coupon']->code} applied — you saved ".money($result['discount']).'.');
    }

    public function removeCoupon(): void
    {
        session()->forget(CouponService::SESSION_KEY);
        $this->dispatch('toast', type: 'info', message: 'Promo code removed.');
    }

    private function changeQuantity(int $itemId, int $delta, CartService $cart): void
    {
        if (($item = $this->ownedItem($itemId, $cart)) !== null) {
            $cart->updateQuantity($item, max(1, $item->quantity + $delta));
            $this->dispatch('cart-updated');
        }
    }

    /**
     * Resolve a cart item only if it belongs to the current cart (mirrors the
     * controller's authorizeItem guard).
     */
    private function ownedItem(int $itemId, CartService $cart): ?CartItem
    {
        $currentCart = $cart->find();
        $item = CartItem::find($itemId);

        return $item && $currentCart && $item->cart_id === $currentCart->id ? $item : null;
    }

    public function render(CartService $cart)
    {
        $summary = $cart->summary();
        $coupon = app(CouponService::class)->resolveForCart($summary, auth()->user());
        $discount = $coupon['discount'] ?? Money::zero();

        return view('livewire.cart.cart-manager', [
            ...$summary,
            'appliedCoupon' => $coupon['coupon'] ?? null,
            'discount' => $discount,
            'total' => $summary['subtotal']->minus($discount),
        ]);
    }
}
