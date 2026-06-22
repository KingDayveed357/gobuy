<?php

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Pricing\Models\Coupon;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Validates and applies a single promo code to a cart. No stacking: one coupon
 * per order. All discounts are computed in integer kobo via {@see Money}.
 */
class CouponService
{
    /**
     * Evaluate a code against the current cart. Returns the coupon and the
     * computed discount, or an error reason.
     *
     * @param  Collection<int, array{item: mixed, price: mixed, lineTotal: Money}>  $lines
     * @return array{ok: bool, reason?: string, coupon?: Coupon, discount?: Money}
     */
    public function evaluate(string $code, Money $subtotal, ?User $user, Collection $lines): array
    {
        $coupon = Coupon::with(['products:id', 'categories:id'])
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])
            ->first();

        if (! $coupon || ! $coupon->is_active) {
            return $this->fail('That code is not valid.');
        }

        $now = now();
        if (($coupon->starts_at && $coupon->starts_at->gt($now)) || ($coupon->expires_at && $coupon->expires_at->lt($now))) {
            return $this->fail('That code has expired or is not yet active.');
        }

        $isWholesale = (bool) $user?->isWholesale();
        if (($coupon->eligibility === 'retail' && $isWholesale) || ($coupon->eligibility === 'wholesale' && ! $isWholesale)) {
            return $this->fail('This code does not apply to your account.');
        }

        if ($coupon->min_cart_value !== null && $subtotal->lessThan(Money::fromNaira($coupon->min_cart_value))) {
            return $this->fail('Your cart does not meet the minimum for this code ('.money(Money::fromNaira($coupon->min_cart_value)).').');
        }

        if ($coupon->usage_limit_total !== null && $coupon->usages()->count() >= $coupon->usage_limit_total) {
            return $this->fail('This code has reached its usage limit.');
        }

        if ($user && $coupon->usage_limit_per_user !== null
            && $coupon->usages()->where('user_id', $user->id)->count() >= $coupon->usage_limit_per_user) {
            return $this->fail('You have already used this code.');
        }

        $applicable = $this->applicableSubtotal($coupon, $lines, $subtotal);

        if (! $applicable->isPositive()) {
            return $this->fail('No items in your cart qualify for this code.');
        }

        return ['ok' => true, 'coupon' => $coupon, 'discount' => $this->discountFor($coupon, $applicable)];
    }

    /**
     * The portion of the subtotal a coupon applies to. An unscoped coupon
     * applies to the whole cart; a product/category-scoped one only to matching
     * lines.
     *
     * @param  Collection<int, array{item: mixed, lineTotal: Money}>  $lines
     */
    public function applicableSubtotal(Coupon $coupon, Collection $lines, Money $subtotal): Money
    {
        $productIds = $coupon->products->pluck('id');
        $categoryIds = $coupon->categories->pluck('id');

        if ($productIds->isEmpty() && $categoryIds->isEmpty()) {
            return $subtotal;
        }

        return $lines->reduce(function (Money $carry, array $line) use ($productIds, $categoryIds): Money {
            $product = $line['item']->variant->product;
            $matches = $productIds->contains($product->id) || $categoryIds->contains($product->category_id);

            return $matches ? $carry->plus($line['lineTotal']) : $carry;
        }, Money::zero());
    }

    /**
     * Discount for a coupon over an applicable amount, capped at that amount.
     */
    public function discountFor(Coupon $coupon, Money $applicable): Money
    {
        $discount = $coupon->type === 'percentage'
            ? $applicable->percentage((float) $coupon->value)
            : Money::fromNaira($coupon->value);

        return $discount->lessThan($applicable) ? $discount : $applicable;
    }

    /**
     * Key under which the applied coupon code is held in the session.
     */
    public const SESSION_KEY = 'cart.coupon';

    /**
     * Re-evaluate the coupon held in the session against a fresh cart summary.
     * A coupon that has become invalid (cart fell below the minimum, expired,
     * limit reached) is silently dropped so the cart never shows a stale code.
     *
     * @param  array{lines: array<int, array{item: mixed, price: mixed, lineTotal: Money}>, subtotal: Money}  $summary
     * @return array{ok: true, coupon: Coupon, discount: Money}|null
     */
    public function resolveForCart(array $summary, ?User $user): ?array
    {
        $code = session(self::SESSION_KEY);
        if (! is_string($code) || $code === '') {
            return null;
        }

        $result = $this->evaluate($code, $summary['subtotal'], $user, collect($summary['lines']));

        if (! $result['ok']) {
            session()->forget(self::SESSION_KEY);

            return null;
        }

        return $result;
    }

    /**
     * Record that a coupon was redeemed on an order.
     */
    public function redeem(Coupon $coupon, ?User $user, Order $order, Money $discount): void
    {
        $coupon->usages()->create([
            'user_id' => $user?->id,
            'order_id' => $order->id,
            'discount_applied' => $discount->toNaira(),
        ]);
    }

    /**
     * @return array{ok: false, reason: string}
     */
    private function fail(string $reason): array
    {
        return ['ok' => false, 'reason' => $reason];
    }
}
