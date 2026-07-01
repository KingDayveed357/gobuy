<?php

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Cart\Services\CartService;
use App\Modules\Logistics\Services\DeliveryFeeService;
use App\Modules\Pricing\Services\CouponService;
use App\Modules\Returns\Services\StoreCreditService;
use App\Support\Money;

class CheckoutCalculator
{
    /**
     * The single session key recording whether the shopper opted to spend store
     * credit on this checkout. Shared by the controller, the Livewire summary,
     * and the place-order action so the flag can never drift between them.
     */
    public const CREDIT_SESSION_KEY = 'checkout.apply_credit';

    public function __construct(
        private readonly CartService $cart,
        private readonly DeliveryFeeService $deliveryFees,
        private readonly CouponService $coupons,
        private readonly StoreCreditService $storeCredit,
    ) {}

    /**
     * Calculates all checkout totals (subtotal, discount, delivery, store credit, total, amount due).
     *
     * @param  array|null  $cartSummary  Optional cached cart summary to avoid resolving cart twice
     */
    public function calculate(
        ?User $user,
        string $deliveryMethod,
        string $state,
        bool $applyCredit = false,
        ?array $cartSummary = null
    ): array {
        $summary = $cartSummary ?? $this->cart->summary();

        $subtotal = $summary['subtotal'] ?? Money::zero();
        $weight = (int) ($summary['weight'] ?? 0);

        // 1. Delivery Quote
        $quote = $this->deliveryFees->quote(
            $deliveryMethod,
            $state,
            $weight,
            $subtotal
        );
        $deliveryFee = $quote['fee'] ?? Money::zero();

        // 2. Coupon Discount
        $couponData = $this->coupons->resolveForCart($summary, $user);
        $discount = $couponData['discount'] ?? Money::zero();

        // 3. Total (Subtotal - Discount + Delivery)
        $discountedSubtotal = $subtotal->minus($discount);
        $total = $discountedSubtotal->plus($deliveryFee);

        // 4. Store Credit
        $creditAvailable = $user ? $this->storeCredit->balanceFor($user) : Money::zero();

        // Ensure we only apply credit if it's available and requested
        $applyCredit = $applyCredit && $creditAvailable->isPositive();
        $creditApplied = $applyCredit && $user ? $this->storeCredit->redeemableFor($user, $total) : Money::zero();

        // 5. Final Amount Due
        $amountDue = $total->minus($creditApplied);

        return [
            'lines' => $summary['lines'] ?? [],
            'subtotal' => $subtotal,
            'weight' => $weight,
            'deliveryFee' => $deliveryFee,
            'deliveryZone' => $quote['zone']?->name ?? null,
            'appliedCoupon' => $couponData['coupon'] ?? null,
            'discount' => $discount,
            'total' => $total,
            'creditAvailable' => $creditAvailable,
            'applyCredit' => $applyCredit,
            'creditApplied' => $creditApplied,
            'amountDue' => $amountDue,
        ];
    }
}
