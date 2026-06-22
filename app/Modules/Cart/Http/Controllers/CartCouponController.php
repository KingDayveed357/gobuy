<?php

namespace App\Modules\Cart\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Pricing\Services\CouponService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CartCouponController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly CouponService $coupons,
    ) {}

    /**
     * Validate and apply a promo code to the current cart (one coupon, no stacking).
     */
    public function store(Request $request): RedirectResponse
    {
        $code = $request->validate([
            'code' => ['required', 'string', 'max:60'],
        ])['code'];

        $summary = $this->cart->summary();

        if (empty($summary['lines'])) {
            return back()->with('error', 'Add items to your cart before applying a code.');
        }

        $result = $this->coupons->evaluate($code, $summary['subtotal'], $request->user(), new Collection($summary['lines']));

        if (! $result['ok']) {
            return back()->with('error', $result['reason']);
        }

        session([CouponService::SESSION_KEY => $result['coupon']->code]);

        return back()->with('status', "Code {$result['coupon']->code} applied — you saved ".money($result['discount']).'.');
    }

    /**
     * Remove the applied promo code from the cart.
     */
    public function destroy(): RedirectResponse
    {
        session()->forget(CouponService::SESSION_KEY);

        return back()->with('status', 'Promo code removed.');
    }
}
