<?php

namespace App\Modules\Returns\Listeners;

use App\Admin\Models\Admin;
use App\Admin\Notifications\AdminAlertNotification;
use App\Modules\Returns\Events\ReturnRequested;
use Illuminate\Support\Facades\Notification;

/**
 * Surfaces a new return request to the admins who process returns, so a
 * request never sits unseen in the queue. Uses the shared admin-alert bell
 * notification (database channel) rather than email — returns are routine.
 */
class NotifyAdminsOfNewReturn
{
    public function handle(ReturnRequested $event): void
    {
        $return = $event->return;
        $orderNumber = $return->order?->order_number ?? '—';

        Notification::send(
            Admin::withAbility('manage_returns'),
            new AdminAlertNotification(
                'New return request',
                "Return {$return->reference} was submitted for order {$orderNumber} and is awaiting review.",
                'important',
                route('admin.returns.show', $return),
                'fa-rotate-left',
            ),
        );
    }
}
