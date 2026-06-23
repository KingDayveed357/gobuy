<?php

namespace App\Modules\Returns\Enums;

/**
 * Customer-stated reason for a return. Some reasons (defective, damaged,
 * wrong item) are "merchant fault" — they flip return shipping to the merchant
 * and require a photo.
 */
enum ReturnReason: string
{
    case Damaged = 'damaged';
    case Defective = 'defective';
    case WrongItem = 'wrong_item';
    case NotAsDescribed = 'not_as_described';
    case ChangedMind = 'changed_mind';
    case BetterPrice = 'better_price';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }

    public function isMerchantFault(): bool
    {
        return in_array($this, [self::Damaged, self::Defective, self::WrongItem, self::NotAsDescribed], true);
    }

    public function requiresPhoto(): bool
    {
        return in_array($this, [self::Damaged, self::Defective, self::WrongItem], true);
    }
}
