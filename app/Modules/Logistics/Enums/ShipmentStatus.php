<?php

namespace App\Modules\Logistics\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Packed = 'packed';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Packed => 'Packed',
            self::Dispatched => 'Dispatched',
            self::InTransit => 'In transit',
            self::Delivered => 'Delivered',
        };
    }

    /**
     * The next stage in the linear fulfilment flow, or null if delivered.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Pending => self::Packed,
            self::Packed => self::Dispatched,
            self::Dispatched => self::InTransit,
            self::InTransit => self::Delivered,
            self::Delivered => null,
        };
    }

    /** Ordered stages for rendering a tracking timeline. */
    public static function timeline(): array
    {
        return [self::Pending, self::Packed, self::Dispatched, self::InTransit, self::Delivered];
    }

    public function position(): int
    {
        return array_search($this, self::timeline(), true);
    }
}
