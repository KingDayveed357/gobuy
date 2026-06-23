<?php

namespace App\Modules\Notification\Services;

use App\Modules\Logistics\Models\Shipment;
use App\Modules\Notification\Notifications\OrderAcceptedMessage;
use App\Modules\Notification\Notifications\ReturnStatusMessage;
use App\Modules\Notification\Notifications\ShipmentStageMessage;
use App\Modules\Order\Models\Order;
use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Support\Facades\Notification;

/**
 * Sends customer-facing SMS/WhatsApp updates at order and delivery milestones.
 * Messages are dispatched as queued, on-demand notifications keyed to the
 * customer's phone (works for guests too).
 */
class CustomerNotifier
{
    public function orderAccepted(Order $order): void
    {
        if (! $order->customer_phone) {
            return;
        }

        Notification::route('messaging', $order->customer_phone)
            ->notify(new OrderAcceptedMessage($order));
    }

    public function shipmentStage(Shipment $shipment): void
    {
        $phone = $shipment->order?->customer_phone;

        if (! $phone) {
            return;
        }

        Notification::route('messaging', $phone)
            ->notify(new ShipmentStageMessage($shipment));
    }

    public function returnUpdate(ReturnRequest $return): void
    {
        $phone = $return->order?->customer_phone;

        if (! $phone) {
            return;
        }

        Notification::route('messaging', $phone)
            ->notify(new ReturnStatusMessage($return));
    }
}
