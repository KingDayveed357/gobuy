<?php

namespace App\Admin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        return $this->severity === 'critical' ? ['database', 'mail'] : ['database'];
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
