<?php

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Support\Money;

/**
 * Decides which payment options a customer may use at checkout. Pay-on-Delivery
 * is retail-only and capped at a configurable Naira threshold.
 */
class PaymentOptionsService
{
    public function podEligible(Money $subtotal, ?User $user): bool
    {
        if (! config('gobuy.pod.enabled')) {
            return false;
        }

        // Wholesale buyers settle on account terms, not POD.
        if ($user?->isWholesale()) {
            return false;
        }

        $threshold = Money::fromNaira(config('gobuy.pod.threshold'));

        return ! $subtotal->lessThan(Money::zero()) && ! $threshold->lessThan($subtotal);
    }
}
