<?php

namespace App\Modules\Logistics\Models;

use App\Modules\Logistics\Enums\ShipmentStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    public const METHOD_HOME = 'home_delivery';

    public const METHOD_PICKUP = 'pickup';

    protected $fillable = [
        'order_id', 'method', 'delivery_zone_id', 'pickup_location_id',
        'weight_g', 'carrier', 'waybill', 'status', 'dispatched_at', 'delivered_at',
    ];

    protected $attributes = [
        'status' => 'pending',
        'weight_g' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'weight_g' => 'integer',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(PickupLocation::class);
    }

    public function isPickup(): bool
    {
        return $this->method === self::METHOD_PICKUP;
    }

    public function methodLabel(): string
    {
        return $this->isPickup() ? 'Pickup' : 'Home delivery';
    }
}
