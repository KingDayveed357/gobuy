<?php

namespace App\Modules\Order\Listeners;

use App\Modules\Notification\Services\CustomerNotifier;
use App\Modules\Order\Events\OrderCancelled;

/**
 * Tells the customer their order was cancelled — covers every cancellation
 * path (failed/abandoned payment, admin cancel, self-service cancel), since
 * they all converge on the OrderCancelled event.
 */
class NotifyCustomerOfCancellation
{
    public function __construct(private readonly CustomerNotifier $notifier) {}

    public function handle(OrderCancelled $event): void
    {
        $this->notifier->orderCancelled($event->order);
    }
}
