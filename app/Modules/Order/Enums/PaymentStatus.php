<?php

namespace App\Modules\Order\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case PartiallyRefunded = 'partially_refunded';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PartiallyRefunded => 'Partially Refunded',
            default => ucfirst($this->value),
        };
    }
}
