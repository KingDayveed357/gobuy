<?php

namespace App\Modules\Returns\Enums;

enum ReturnItemResolution: string
{
    case Refunded = 'refunded';
    case Replaced = 'replaced';
    case RejectedReturnToCustomer = 'rejected_return_to_customer';
    case RejectedDiscarded = 'rejected_discarded';

    public function label(): string
    {
        return match ($this) {
            self::Refunded => 'Refunded',
            self::Replaced => 'Replaced',
            self::RejectedReturnToCustomer => 'Rejected (Return to Customer)',
            self::RejectedDiscarded => 'Rejected (Discarded)',
        };
    }
}
