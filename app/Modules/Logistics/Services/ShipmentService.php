<?php

namespace App\Modules\Logistics\Services;

use App\Modules\Logistics\Contracts\ShippingProvider;
use App\Modules\Logistics\Enums\ShipmentStatus;
use App\Modules\Logistics\Models\DeliveryZone;
use App\Modules\Logistics\Models\Shipment;
use App\Modules\Notification\Services\CustomerNotifier;
use App\Modules\Order\DTOs\CheckoutData;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStatusService;
use RuntimeException;

class ShipmentService
{
    public function __construct(
        private readonly OrderStatusService $orderStatus,
        private readonly ShippingProvider $carrier,
        private readonly CustomerNotifier $notifier,
    ) {}

    /**
     * Create the shipment record that accompanies a newly placed order.
     */
    public function createForOrder(Order $order, CheckoutData $data, int $weightGrams, ?DeliveryZone $zone): Shipment
    {
        return $order->shipment()->create([
            'method' => $data->deliveryMethod,
            'delivery_zone_id' => $data->isPickup() ? null : $zone?->id,
            'pickup_location_id' => $data->isPickup() ? $data->pickupLocationId : null,
            'weight_g' => $weightGrams,
            'carrier' => $data->isPickup() ? null : 'GoBuy Dispatch',
            'status' => ShipmentStatus::Pending,
        ]);
    }

    /**
     * Advance a shipment to the next fulfilment stage, generating a waybill on
     * dispatch and syncing the parent order's status where appropriate.
     */
    public function advance(Shipment $shipment): Shipment
    {
        $next = $shipment->status->next();

        if ($next === null) {
            throw new RuntimeException('This shipment has already been delivered.');
        }

        $shipment->status = $next;

        if ($next === ShipmentStatus::Dispatched) {
            $shipment->waybill ??= $this->carrier->generateWaybill($shipment);
            $shipment->dispatched_at = now();
            $this->syncOrderStatus($shipment->order, OrderStatus::Shipped, 'Shipment dispatched');
        }

        if ($next === ShipmentStatus::Delivered) {
            $shipment->delivered_at = now();
            $this->syncOrderStatus($shipment->order, OrderStatus::Delivered, 'Shipment delivered');
        }

        $shipment->save();

        // Notify the customer of the new delivery stage (SMS/WhatsApp).
        $this->notifier->shipmentStage($shipment);

        return $shipment;
    }

    private function syncOrderStatus(Order $order, OrderStatus $target, string $note): void
    {
        if ($order->status->canTransitionTo($target)) {
            $this->orderStatus->transitionTo($order, $target, $note);
        }
    }
}
