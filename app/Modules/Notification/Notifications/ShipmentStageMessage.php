<?php

namespace App\Modules\Notification\Notifications;

use App\Modules\Logistics\Enums\ShipmentStatus;
use App\Modules\Logistics\Models\Shipment;
use App\Modules\Notification\Channels\MessagingChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ShipmentStageMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Shipment $shipment) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [MessagingChannel::class];
    }

    public function toMessaging(object $notifiable): string
    {
        $number = $this->shipment->order->order_number;

        return match ($this->shipment->status) {
            ShipmentStatus::Packed => "Your GoBuy order {$number} has been packed and is ready for dispatch.",
            ShipmentStatus::Dispatched => $this->shipment->isPickup()
                ? "Your GoBuy order {$number} is ready for collection at {$this->shipment->pickupLocation?->name}."
                : "Good news! Order {$number} has been dispatched (waybill {$this->shipment->waybill}). Track at ".route('orders.track.form'),
            ShipmentStatus::InTransit => "Your GoBuy order {$number} is on its way to you.",
            ShipmentStatus::Delivered => "Order {$number} has been delivered. Thank you for shopping with GoBuy!",
            default => '',
        };
    }
}
