<?php

namespace App\Modules\Logistics\Listeners;

use App\Modules\Logistics\Enums\ShipmentStatus;
use App\Modules\Logistics\Services\ShipmentService;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Events\OrderStatusChanged;

/**
 * Keeps the shipment in step when an order is moved to a fulfilment state
 * directly (e.g. via the admin order-status dropdown), closing the order→shipment
 * desync. Only maps the two states with a clear shipment equivalent; the
 * shipment→order direction is already handled inside ShipmentService::advance.
 */
class SyncShipmentToOrderStatus
{
    public function __construct(private readonly ShipmentService $shipments) {}

    public function handle(OrderStatusChanged $event): void
    {
        // Fresh read — the shipment may have just been saved by advance().
        $shipment = $event->order->shipment()->first();

        if ($shipment === null) {
            return;
        }

        $target = match ($event->to) {
            OrderStatus::Shipped => ShipmentStatus::Dispatched,
            OrderStatus::Delivered => ShipmentStatus::Delivered,
            default => null,
        };

        if ($target !== null) {
            $this->shipments->advanceToStage($shipment, $target);
        }
    }
}
