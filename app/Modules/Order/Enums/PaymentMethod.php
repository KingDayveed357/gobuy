<?php

namespace App\Modules\Order\Enums;

enum PaymentMethod: string
{
    case Paystack = 'paystack';
    case BankTransfer = 'bank_transfer';
    case PayOnDelivery = 'pod';
    case Cash = 'cash';
    case PosTerminal = 'pos_terminal';

    public function label(): string
    {
        return match ($this) {
            self::Paystack => 'Card / Bank / USSD',
            self::BankTransfer => 'Bank transfer',
            self::PayOnDelivery => 'Pay on delivery',
            self::Cash => 'Cash',
            self::PosTerminal => 'POS terminal',
        };
    }

    public function isManual(): bool
    {
        return $this !== self::Paystack;
    }

    /**
     * Tender types an in-store / walk-in sale accepts — no online gateway, no
     * pay-on-delivery. The storefront checkout never offers these.
     *
     * @return list<self>
     */
    public static function inStore(): array
    {
        return [self::Cash, self::PosTerminal, self::BankTransfer];
    }
}
