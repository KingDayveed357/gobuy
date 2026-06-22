<?php

namespace Tests\Feature\Admin;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_admin_can_view_orders_list(): void
    {
        $order = Order::factory()->create();

        $this->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_orders_can_be_filtered_by_status(): void
    {
        $paid = Order::factory()->paid()->create();
        $pending = Order::factory()->create();

        $this->get(route('admin.orders.index', ['status' => 'paid']))
            ->assertOk()
            ->assertSee($paid->order_number)
            ->assertDontSee($pending->order_number);
    }

    public function test_admin_can_view_order_detail(): void
    {
        $order = Order::factory()->paid()->create();
        $order->items()->create(['product_variant_id' => null, 'name' => 'Detail Item', 'sku' => 'D1', 'unit_price' => 1000, 'quantity' => 1, 'line_total' => 1000]);

        $this->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Detail Item');
    }

    public function test_admin_can_advance_order_status(): void
    {
        $order = Order::factory()->paid()->create();

        $this->post(route('admin.orders.status', $order), ['status' => 'processing', 'note' => 'Packing now'])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => 'processing',
            'note' => 'Packing now',
        ]);
    }

    public function test_invalid_transition_is_rejected_with_error(): void
    {
        $order = Order::factory()->paid()->create();

        $this->post(route('admin.orders.status', $order), ['status' => 'delivered'])
            ->assertSessionHas('error');

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }
}
