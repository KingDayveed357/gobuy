<?php

namespace App\Modules\Returns\Enums;

/**
 * What an agent decides to do with a physically inspected return item.
 */
enum ReturnItemDisposition: string
{
    case Restock = 'restock';   // back to sellable stock
    case Damaged = 'damaged';   // received but not resellable (no restock)
    case Reject = 'reject';     // claim denied for this line (no refund)

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isApproved(): bool
    {
        return $this !== self::Reject;
    }

    public function shouldRestock(): bool
    {
        return $this === self::Restock;
    }
}
