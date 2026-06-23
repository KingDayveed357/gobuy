<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Notification\Channels\MessagingChannel;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReturnStatusMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ReturnRequest $return) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [MessagingChannel::class];
    }

    public function toMessaging(object $notifiable): string
    {
        $ref = $this->return->reference;
        $amount = money($this->return->refunded_total);

        return match ($this->return->status) {
            ReturnStatus::AwaitingShipment => "Your GoBuy return {$ref} is approved. Please ship the item back"
                .($this->return->returnShipment ? " using tracking {$this->return->returnShipment->tracking_reference}." : '.')
                .' Details: '.route('account.returns.show', $ref),
            ReturnStatus::InfoRequested => "We need a little more information on your GoBuy return {$ref}. Please reply in your account: ".route('account.returns.show', $ref),
            ReturnStatus::Refunded => "Your GoBuy return {$ref} is complete — {$amount} has been refunded to your original payment method.",
            ReturnStatus::Credited => "Your GoBuy return {$ref} is complete — {$amount} has been added to your store credit.",
            ReturnStatus::Rejected => "Your GoBuy return {$ref} could not be approved. Please contact support if you have questions.",
            default => '',
        };
    }
}
