<?php

namespace App\Admin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * A single, reusable operational alert for administrators. Channels are chosen by
 * severity (critical also emails). The `url` deep-link + structured payload make
 * this ready for real-time broadcast / PWA push with no change to callers.
 */
class AdminAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'critical'|'important'|'info'  $severity
     */
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly string $severity = 'important',
        public readonly ?string $url = null,
        public readonly string $icon = 'fa-triangle-exclamation',
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = $this->severity === 'critical' ? ['database', 'mail'] : ['database'];

        // Also deliver as a browser/PWA push when the recipient has opted in.
        // The channel itself no-ops for recipients without subscriptions.
        if (method_exists($notifiable, 'pushSubscriptions')) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * The in-app bell must appear immediately — operational awareness cannot wait
     * on a queue worker — so the database channel is delivered synchronously while
     * the slower mail/push channels ride the default (queued) connection.
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
            ->title($this->title)
            ->body($this->message)
            ->icon(asset('theme/img/favicons/apple-touch-icon.png'))
            ->badge(asset('theme/img/favicons/favicon-32x32.png'))
            ->tag('gobuy-'.$this->severity)
            ->data(['url' => $this->url ?? url('/admin')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'alert',
            'severity' => $this->severity,
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'url' => $this->url, // deep-link — reused by the bell today, push tomorrow
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[gobuy admin] {$this->title}")
            ->greeting($this->title)
            ->line($this->message);

        return $this->url ? $mail->action('Open admin', $this->url) : $mail;
    }
}
