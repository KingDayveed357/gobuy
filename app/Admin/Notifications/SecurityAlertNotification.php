<?php

namespace App\Admin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app alert to the platform owner(s) about a sensitive, security-relevant
 * event (staff invited/suspended/archived, role changed, …).
 */
class SecurityAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $title,
        public readonly string $message,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'security',
            'icon' => 'fa-shield-halved',
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
