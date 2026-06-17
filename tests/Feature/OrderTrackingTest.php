<?php

namespace Tests\Feature;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_track_form_renders(): void
    {
        $this->get(route('orders.track.form'))->assertOk()->assertSee('Track your order');
    }

    public function test_tracking_shows_timeline_for_matching_order_and_email(): void
    {
        $order = Order::factory()->create([
            'customer_email' => 'buyer@example.com',
            'status' => OrderStatus::Paid,
        ]);
        $order->statusHistories()->create(['status' => OrderStatus::Pending, 'note' => 'Order placed']);
        $order->statusHistories()->create(['status' => OrderStatus::Paid, 'note' => 'Payment confirmed']);

        $this->post(route('orders.track'), [
            'order_number' => $order->order_number,
            'email' => 'buyer@example.com',
        ])
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Payment confirmed');
    }

    public function test_tracking_rejects_wrong_email(): void
    {
        $order = Order::factory()->create(['customer_email' => 'real@example.com']);

        $this->post(route('orders.track'), [
            'order_number' => $order->order_number,
            'email' => 'wrong@example.com',
        ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_tracking_validates_input(): void
    {
        $this->post(route('orders.track'), [])
            ->assertSessionHasErrors(['order_number', 'email']);
    }
}
