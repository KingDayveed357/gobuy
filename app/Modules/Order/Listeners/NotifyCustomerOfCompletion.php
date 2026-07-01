<?php

namespace App\Modules\Order\Listeners;

use App\Modules\Notification\Services\CustomerNotifier;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Events\OrderStatusChanged;

/**
 * Thanks the customer once their order reaches the terminal Completed state and
 * invites feedback. Distinct from the delivery message (sent at the shipment
 * Delivered stage) — Completed is the post-delivery wrap-up.
 */
class NotifyCustomerOfCompletion
{
    public function __construct(private readonly CustomerNotifier $notifier) {}

    public function handle(OrderStatusChanged $event): void
    {
        if ($event->to === OrderStatus::Completed) {
            $this->notifier->orderCompleted($event->order);
        }
    }
}
