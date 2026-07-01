<?php

namespace App\Modules\Order\Listeners;

use App\Admin\Models\Admin;
use App\Admin\Notifications\NewPaidOrderNotification;
use App\Modules\Notification\Services\CustomerNotifier;
use App\Modules\Order\Events\OrderPaid;
use App\Modules\Order\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Runs synchronously (NOT ShouldQueue) so the admin's in-app notification is
 * created the instant an order is paid — operational awareness must never wait
 * on a queue worker. The slow, external channels stay asynchronous on their own:
 * the confirmation email is pushed with Mail::queue and the customer SMS is a
 * queued (ShouldQueue) notification. Only the fast DB write happens in-request.
 */
class SendOrderAcceptedNotifications
{
    public function __construct(
        private readonly CustomerNotifier $notifier
    ) {}

    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        Mail::to($order->customer_email)->queue(new OrderConfirmationMail($order));

        $this->notifier->orderAccepted($order);

        Notification::send(Admin::withAbility('manage_orders'), new NewPaidOrderNotification($order));
    }
}
