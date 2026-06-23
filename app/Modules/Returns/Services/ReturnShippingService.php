<?php

namespace App\Modules\Returns\Services;

use App\Modules\Returns\Models\ReturnRequest;
use App\Modules\Returns\Models\ReturnShipment;
use Illuminate\Support\Str;

/**
 * The reverse-logistics leg. Issues a return label/tracking reference in-house
 * (the seam where a carrier return-pickup API would plug in) and tracks the
 * parcel's progress back to the warehouse. The payer (customer vs merchant for
 * a merchant-fault return) is carried from the return request.
 */
class ReturnShippingService
{
    /**
     * Issue (or return the existing) return label for an approved return.
     * Idempotent — one shipment per return.
     */
    public function issueLabel(ReturnRequest $return): ReturnShipment
    {
        return $return->returnShipment()->firstOr(fn () => $return->returnShipment()->create([
            'tracking_reference' => $this->generateReference(),
            'carrier' => config('gobuy.returns.carrier', 'GoBuy Returns'),
            'payer' => $return->return_shipping_payer ?? 'customer',
        ]));
    }

    public function markShipped(ReturnShipment $shipment): void
    {
        if ($shipment->shipped_at === null) {
            $shipment->update(['shipped_at' => now()]);
        }
    }

    public function markReceived(ReturnShipment $shipment): void
    {
        if ($shipment->received_at === null) {
            $shipment->update(['received_at' => now()]);
        }
    }

    private function generateReference(): string
    {
        do {
            $reference = 'RWB-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (ReturnShipment::where('tracking_reference', $reference)->exists());

        return $reference;
    }
}
