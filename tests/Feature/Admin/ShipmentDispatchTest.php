<?php

namespace Tests\Feature\Admin;

use App\Modules\Logistics\Enums\ShipmentStatus;
use App\Modules\Logistics\Models\Shipment;
use App\Modules\Logistics\Services\ShipmentService;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ShipmentDispatchTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    private function shipmentForProcessingOrder(): Shipment
    {
        $order = Order::factory()->paid()->create();
        $order->update(['status' => OrderStatus::Processing]);

        return $order->shipment()->create([
            'method' => Shipment::METHOD_HOME,
            'status' => ShipmentStatus::Pending,
        ]);
    }

    public function test_admin_advances_stages_and_dispatch_generates_a_waybill(): void
    {
        $this->actingAsAdmin('Super Admin');
        $shipment = $this->shipmentForProcessingOrder();

        // Pending -> Packed
        $this->post(route('admin.shipments.advance', $shipment))->assertRedirect();
        $this->assertSame(ShipmentStatus::Packed, $shipment->fresh()->status);

        // Packed -> Dispatched (waybill + order Shipped)
        $this->post(route('admin.shipments.advance', $shipment))->assertRedirect();
        $shipment->refresh();
        $this->assertSame(ShipmentStatus::Dispatched, $shipment->status);
        $this->assertNotNull($shipment->waybill);
        $this->assertNotNull($shipment->dispatched_at);
        $this->assertSame(OrderStatus::Shipped, $shipment->order->fresh()->status);
    }

    public function test_delivering_marks_the_order_delivered(): void
    {
        $this->actingAsAdmin('Super Admin');
        $shipment = $this->shipmentForProcessingOrder();

        foreach (range(1, 4) as $_) {
            $this->post(route('admin.shipments.advance', $shipment));
        }

        $shipment->refresh();
        $this->assertSame(ShipmentStatus::Delivered, $shipment->status);
        $this->assertNotNull($shipment->delivered_at);
        $this->assertSame(OrderStatus::Delivered, $shipment->order->fresh()->status);
    }

    public function test_moving_the_order_status_directly_advances_the_shipment(): void
    {
        $shipment = $this->shipmentForProcessingOrder(); // order Processing, shipment Pending
        $status = app(OrderStatusService::class);

        // Order → Shipped (e.g. via the order-status dropdown) now pulls the shipment forward.
        $status->transitionTo($shipment->order, OrderStatus::Shipped);
        $shipment->refresh();
        $this->assertSame(ShipmentStatus::Dispatched, $shipment->status);
        $this->assertNotNull($shipment->dispatched_at);

        $status->transitionTo($shipment->order->fresh(), OrderStatus::Delivered);
        $this->assertSame(ShipmentStatus::Delivered, $shipment->fresh()->status);
    }

    public function test_advancing_the_shipment_is_not_double_processed_by_the_event(): void
    {
        $shipment = $this->shipmentForProcessingOrder();
        $shipments = app(ShipmentService::class);

        $shipments->advance($shipment);            // Pending → Packed
        $shipments->advance($shipment->fresh());   // Packed → Dispatched (+ order Shipped → event → listener no-ops)

        $fresh = $shipment->fresh();
        $this->assertSame(ShipmentStatus::Dispatched, $fresh->status); // not over-advanced by the listener
        $this->assertSame(OrderStatus::Shipped, $fresh->order->fresh()->status);
    }
}
