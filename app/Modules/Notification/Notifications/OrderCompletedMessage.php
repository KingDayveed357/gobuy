<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Notification\Channels\MessagingChannel;
use App\Modules\Order\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderCompletedMessage extends Notification implements ShouldQueue
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

        return "Hi {$name}, your GoBuy order {$this->order->order_number} is complete — "
            .'thank you for shopping with us! We’d love your feedback: '
            .route('orders.track.form');
    }
}
