<?php

namespace App\Modules\Logistics\Services;

use App\Modules\Logistics\Models\DeliveryZone;
use App\Modules\Logistics\Models\DeliveryZoneState;
use App\Modules\Logistics\Models\Shipment;
use App\Support\Money;

/**
 * Computes delivery fees from the zone a state belongs to plus the order's
 * total weight. Pickup is always free. All money is integer kobo.
 */
class DeliveryFeeService
{
    /**
     * Resolve the delivery zone covering a state, falling back to Nationwide.
     */
    public function zoneForState(string $state): ?DeliveryZone
    {
        $mapped = DeliveryZoneState::with('zone')
            ->whereRaw('LOWER(state) = ?', [mb_strtolower(trim($state))])
            ->first();

        if ($mapped && $mapped->zone && $mapped->zone->is_active) {
            return $mapped->zone;
        }

        return DeliveryZone::active()->where('slug', 'nationwide')->first()
            ?? DeliveryZone::active()->orderByDesc('sort_order')->first();
    }

    /**
     * Quote a fee for the chosen fulfilment method.
     *
     * @return array{fee: Money, zone: ?DeliveryZone}
     */
    public function quote(string $method, string $state, int $weightGrams, Money $subtotal): array
    {
        if ($method === Shipment::METHOD_PICKUP) {
            return ['fee' => Money::zero(), 'zone' => null];
        }

        $zone = $this->zoneForState($state);

        if (! $zone) {
            return ['fee' => Money::zero(), 'zone' => null];
        }

        // Free delivery above a threshold.
        if ($zone->free_over_subtotal !== null && ! $subtotal->lessThan($zone->free_over_subtotal)) {
            return ['fee' => Money::zero(), 'zone' => $zone];
        }

        $kilograms = max(0, (int) ceil($weightGrams / 1000));
        $fee = $zone->base_fee->plus($zone->per_kg_fee->times($kilograms));

        return ['fee' => $fee, 'zone' => $zone];
    }
}
