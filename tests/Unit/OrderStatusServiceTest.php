<?php

namespace Tests\Unit;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\InvalidOrderTransition;
use App\Modules\Order\Services\OrderStatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrderStatusServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_enum_allows_valid_forward_transitions(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Paid));
        $this->assertTrue(OrderStatus::Paid->canTransitionTo(OrderStatus::Processing));
        $this->assertTrue(OrderStatus::Shipped->canTransitionTo(OrderStatus::Delivered));
    }

    public function test_enum_rejects_invalid_transitions(): void
    {
        $this->assertFalse(OrderStatus::Pending->canTransitionTo(OrderStatus::Shipped));
        $this->assertFalse(OrderStatus::Completed->canTransitionTo(OrderStatus::Pending));
    }

    public function test_transition_updates_status_and_records_history(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);
        $service = app(OrderStatusService::class);

        $service->transitionTo($order, OrderStatus::Paid, 'Payment confirmed');

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'paid',
            'note' => 'Payment confirmed',
        ]);
    }

    public function test_invalid_transition_throws(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);
        $service = app(OrderStatusService::class);

        $this->expectException(InvalidOrderTransition::class);

        $service->transitionTo($order, OrderStatus::Shipped);
    }

    public function test_same_status_transition_is_a_noop(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Pending]);
        $service = app(OrderStatusService::class);

        $service->transitionTo($order, OrderStatus::Pending);

        $this->assertDatabaseCount('order_status_histories', 0);
    }
}
