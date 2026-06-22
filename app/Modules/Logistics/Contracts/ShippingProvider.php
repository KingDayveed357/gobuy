<?php

namespace App\Modules\Logistics\Contracts;

use App\Modules\Logistics\Models\Shipment;

/**
 * Abstraction over a fulfilment carrier. The manual provider generates an
 * in-house waybill; third-party providers (GIG, Sendbox) can implement this
 * same contract and book a consignment with the carrier's API.
 */
interface ShippingProvider
{
    /**
     * Book/generate a waybill (consignment note) for the shipment and return
     * the tracking reference.
     */
    public function generateWaybill(Shipment $shipment): string;
}
