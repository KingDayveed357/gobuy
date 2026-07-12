<?php

namespace App\Modules\Operations\Purchasing\Enums;

/**
 * The life of a purchase order: raised as a draft, placed with the supplier, then
 * received into stock (in one or several deliveries) or cancelled before delivery.
 */
enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Ordered = 'ordered';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Ordered => 'Ordered',
            self::PartiallyReceived => 'Partially received',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Phoenix badge tone for the status pill. */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Ordered => 'info',
            self::PartiallyReceived => 'warning',
            self::Received => 'success',
            self::Cancelled => 'danger',
        };
    }

    /** Whether more goods can still be received against a PO in this state. */
    public function canReceive(): bool
    {
        return in_array($this, [self::Ordered, self::PartiallyReceived], true);
    }
}
