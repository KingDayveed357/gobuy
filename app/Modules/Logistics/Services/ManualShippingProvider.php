<?php

namespace App\Modules\Logistics\Services;

use App\Modules\Logistics\Contracts\ShippingProvider;
use App\Modules\Logistics\Models\Shipment;
use Illuminate\Support\Str;

/**
 * In-house dispatch: generates a unique GoBuy waybill number. No external API.
 */
class ManualShippingProvider implements ShippingProvider
{
    public function generateWaybill(Shipment $shipment): string
    {
        do {
            $waybill = 'WB-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Shipment::where('waybill', $waybill)->exists());

        return $waybill;
    }
}
