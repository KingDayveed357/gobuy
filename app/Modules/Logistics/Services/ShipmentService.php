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
        }

        if ($next === ShipmentStatus::Delivered) {
            $shipment->delivered_at = now();
        }

        // Persist the shipment BEFORE syncing the order. OrderStatusChanged fires
        // (after-commit) and its listener reloads the shipment — by then it is
        // already at the new stage, so the sync is a no-op (no re-entrant loop,
        // no double notification).
        $shipment->save();

        if ($next === ShipmentStatus::Dispatched) {
            $this->syncOrderStatus($shipment->order, OrderStatus::Shipped, 'Shipment dispatched');
        }

        if ($next === ShipmentStatus::Delivered) {
            $this->syncOrderStatus($shipment->order, OrderStatus::Delivered, 'Shipment delivered');
        }

        // Notify the customer of the new delivery stage (SMS/WhatsApp).
        $this->notifier->shipmentStage($shipment);

        return $shipment;
    }

    /**
     * Align a shipment to the stage implied by a direct order-status change (e.g.
     * an admin moved the order to Shipped/Delivered without touching the dispatch
     * console). Forward-only — never regresses — and never syncs back to the order
     * (which is already at the target), so it cannot re-enter the order↔shipment
     * loop. Notifies the customer only when it actually moves the stage.
     */
    public function advanceToStage(Shipment $shipment, ShipmentStatus $target): void
    {
        if ($shipment->status->position() >= $target->position()) {
            return; // already at or beyond this stage
        }

        $shipment->status = $target;

        if ($target->position() >= ShipmentStatus::Dispatched->position() && $shipment->dispatched_at === null) {
            $shipment->waybill ??= $this->carrier->generateWaybill($shipment);
            $shipment->dispatched_at = now();
        }

        if ($target === ShipmentStatus::Delivered && $shipment->delivered_at === null) {
            $shipment->delivered_at = now();
        }

        $shipment->save();
        $this->notifier->shipmentStage($shipment);
    }

    private function syncOrderStatus(Order $order, OrderStatus $target, string $note): void
    {
        if ($order->status->canTransitionTo($target)) {
            $this->orderStatus->transitionTo($order, $target, $note);
        }
    }
}
