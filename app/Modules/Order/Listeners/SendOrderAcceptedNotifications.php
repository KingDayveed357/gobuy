<?php

namespace App\Modules\Order\Listeners;

use App\Admin\Models\Admin;
use App\Admin\Notifications\NewPaidOrderNotification;
use App\Modules\Notification\Services\CustomerNotifier;
use App\Modules\Order\Events\OrderPaid;
use App\Modules\Order\Mail\OrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendOrderAcceptedNotifications implements ShouldQueue
{
    public function __construct(
        private readonly CustomerNotifier $notifier
    ) {}

    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        Mail::to($order->customer_email)->queue(new OrderConfirmationMail($order));

        $this->notifier->orderAccepted($order);

        $admins = Admin::where('is_active', true)->get()
            ->filter(fn (Admin $admin) => $admin->can('manage_orders'));
            
        Notification::send($admins, new NewPaidOrderNotification($order));
    }
}
