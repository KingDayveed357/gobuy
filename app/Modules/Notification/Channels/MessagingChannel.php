<?php

namespace App\Modules\Notification\Channels;

use App\Modules\Notification\Contracts\MessageChannel;
use Illuminate\Notifications\Notification;

/**
 * Laravel notification channel bridging the framework's notification system to
 * the configured {@see MessageChannel} transport. Notifications opt in by
 * returning this class from via() and implementing toMessaging().
 */
class MessagingChannel
{
    public function __construct(private readonly MessageChannel $transport) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationFor('messaging', $notification);

        if (! $to || ! method_exists($notification, 'toMessaging')) {
            return;
        }

        $message = $notification->toMessaging($notifiable);

        if ($message !== '') {
            $this->transport->send((string) $to, $message);
        }
    }
}
