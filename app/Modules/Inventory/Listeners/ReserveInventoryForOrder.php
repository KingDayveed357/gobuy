<?php

namespace App\Modules\Inventory\Listeners;

use App\Modules\Order\Events\OrderPlaced;

class ReserveInventoryForOrder
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        // No-op for MVP.
        // In a complete implementation, this listener would transition the temporary 
        // stock reservation held by the Cart (TTL-based) into a firm reservation 
        // held by the Order, until the payment is either completed or abandoned.
    }
}
