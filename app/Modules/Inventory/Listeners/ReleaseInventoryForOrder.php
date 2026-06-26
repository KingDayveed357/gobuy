<?php

namespace App\Modules\Inventory\Listeners;

use App\Modules\Order\Events\OrderCancelled;

class ReleaseInventoryForOrder
{
    /**
     * Handle the event.
     */
    public function handle(OrderCancelled $event): void
    {
        // No-op for MVP.
        // Order reservations (if transitioned from the Cart) would be released here.
        // Currently, cart reservations simply expire via TTL if the user abandons checkout.
    }
}
