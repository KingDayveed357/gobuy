<?php

namespace App\Modules\Order\Enums;

enum PaymentMethod: string
{
    case Paystack = 'paystack';
    case BankTransfer = 'bank_transfer';
    case PayOnDelivery = 'pod';

    public function label(): string
    {
        return match ($this) {
            self::Paystack => 'Card / Bank / USSD',
            self::BankTransfer => 'Bank transfer',
            self::PayOnDelivery => 'Pay on delivery',
        };
    }

    public function isManual(): bool
    {
        return $this !== self::Paystack;
    }
}
