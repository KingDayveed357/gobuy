<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Notification\Channels\MessagingChannel;
use App\Modules\Order\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderAcceptedMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [MessagingChannel::class];
    }

    public function toMessaging(object $notifiable): string
    {
        $name = str($this->order->customer_name)->before(' ');

        return "Hi {$name}, your GoBuy order {$this->order->order_number} is confirmed "
            .'(total '.money($this->order->total)."). We'll text you delivery updates. "
            .'Track it at '.route('orders.track.form');
    }
}
