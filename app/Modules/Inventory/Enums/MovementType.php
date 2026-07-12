<?php

namespace App\Modules\Inventory\Enums;

/**
 * Why a stock movement happened. The append-only vocabulary of the inventory
 * ledger — the audit trail's reason codes. CO-1 uses Opening, Sale, Return and
 * Adjustment; the rest are reserved for the operations modules that record them
 * (purchasing, transfers, stock counts, damage).
 */
enum MovementType: string
{
    case Opening = 'opening';
    case Sale = 'sale';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Purchase = 'purchase';
    case Receipt = 'receipt';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case Damage = 'damage';
    case Count = 'count';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Opening balance',
            self::Sale => 'Sale',
            self::Return => 'Return',
            self::Adjustment => 'Adjustment',
            self::Purchase => 'Purchase',
            self::Receipt => 'Goods received',
            self::TransferOut => 'Transfer out',
            self::TransferIn => 'Transfer in',
            self::Damage => 'Damage / write-off',
            self::Count => 'Stock count',
        };
    }
}
