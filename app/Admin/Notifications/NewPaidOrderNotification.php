<?php

namespace App\Admin\Notifications;

use App\Modules\Order\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewPaidOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Also push to the browser/desktop when the admin has opted in; the
        // channel no-ops for admins without a subscription.
        if (method_exists($notifiable, 'pushSubscriptions')) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * The in-app bell is delivered synchronously (immediate operational
     * awareness); only the push channel rides the queue.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('New paid order '.$this->order->order_number)
            ->body(($this->order->customer_name ?? 'A customer').' · '.money($this->order->total))
            ->icon(asset('theme/img/favicons/apple-touch-icon.png'))
            ->badge(asset('theme/img/favicons/favicon-32x32.png'))
            ->tag('gobuy-order')
            ->data(['url' => route('admin.orders.show', $this->order->order_number)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_paid_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_kobo' => $this->order->total->kobo,
            'customer' => $this->order->customer_name,
        ];
    }
}
