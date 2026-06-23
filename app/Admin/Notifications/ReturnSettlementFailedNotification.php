<?php

namespace App\Admin\Notifications;

use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Alerts admins when a return's gateway refund is declined so it can be retried
 * or handled manually instead of silently stalling.
 */
class ReturnSettlementFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ReturnRequest $return) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'return_settlement_failed',
            'reference' => $this->return->reference,
            'order_number' => $this->return->order?->order_number,
            'return_id' => $this->return->id,
        ];
    }
}
