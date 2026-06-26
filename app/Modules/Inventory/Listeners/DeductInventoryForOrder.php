<?php

namespace App\Modules\Inventory\Listeners;

use App\Modules\Order\Events\OrderPaid;

class DeductInventoryForOrder
{
    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        // No-op.
        // Inventory is currently deducted synchronously inside PaymentService::completeOrder()
        // within the main DB transaction to guarantee atomicity. This listener exists
        // as a placeholder for future refactoring if we move stock deduction to an async job.
    }
}
