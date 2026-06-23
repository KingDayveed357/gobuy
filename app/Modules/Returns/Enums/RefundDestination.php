<?php

namespace App\Modules\Returns\Enums;

/**
 * Where a settled return's money goes. Store credit is the default (cheapest,
 * works for POD/bank-transfer orders that never paid online); original-method
 * is offered for gateway-paid (Paystack) orders.
 */
enum RefundDestination: string
{
    case StoreCredit = 'store_credit';
    case Original = 'original';

    public function label(): string
    {
        return match ($this) {
            self::StoreCredit => 'Store credit',
            self::Original => 'Original payment method',
        };
    }
}
