<?php

namespace App\Modules\Returns\Listeners;

use App\Modules\Notification\Services\CustomerNotifier;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Events\ReturnStatusChanged;

/**
 * Sends the customer a proactive update on the status changes that matter to
 * them — kept out of the state machine so messaging is a pluggable concern.
 */
class SendReturnStatusNotification
{
    private const NOTIFY_STATUSES = [
        ReturnStatus::AwaitingShipment,
        ReturnStatus::InfoRequested,
        ReturnStatus::Refunded,
        ReturnStatus::Credited,
        ReturnStatus::Rejected,
    ];

    public function __construct(private readonly CustomerNotifier $notifier) {}

    public function handle(ReturnStatusChanged $event): void
    {
        if (in_array($event->to, self::NOTIFY_STATUSES, true)) {
            $this->notifier->returnUpdate($event->return);
        }
    }
}
