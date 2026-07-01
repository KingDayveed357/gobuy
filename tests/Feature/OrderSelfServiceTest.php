<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Customer self-service on their own orders: secure access to order pages,
 * retrying an abandoned payment, and cancelling an unpaid order.
 */
class OrderSelfServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function paystackOrderFor(User $user, array $overrides = []): Order
    {
        return Order::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Failed,
            'payment_method' => PaymentMethod::Paystack,
            'total' => Money::fromNaira(5000),
        ], $overrides));
    }

    public function test_order_pages_are_not_enumerable_by_strangers(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        // A different signed-in customer and an anonymous guest are both blocked.
        $this->actingAs(User::factory()->create())->get(route('orders.success', $order))->assertForbidden();
        $this->get(route('orders.success', $order))->assertForbidden();

        // The owner can view it.
        $this->actingAs($owner)->get(route('orders.success', $order))->assertOk();
    }

    public function test_a_guest_can_view_an_order_they_just_placed_in_this_session(): void
    {
        $order = Order::factory()->create(['user_id' => null]);

        $this->withSession(['viewable_orders' => [$order->id]])
            ->get(route('orders.success', $order))->assertOk();
    }

    public function test_customer_can_retry_an_abandoned_payment(): void
    {
        Http::fake(['*/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://paystack.test/redirect'],
        ])]);
        $user = User::factory()->create();
        $order = $this->paystackOrderFor($user);

        $this->actingAs($user)->post(route('orders.retry', $order))
            ->assertRedirect('https://paystack.test/redirect');

        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'pending']);
    }

    public function test_a_paid_order_cannot_be_retried(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->paid()->create(['user_id' => $user->id, 'payment_method' => PaymentMethod::Paystack]);

        $this->actingAs($user)->post(route('orders.retry', $order))
            ->assertRedirect(route('orders.success', $order))
            ->assertSessionHas('error');
    }

    public function test_customer_can_cancel_an_unpaid_order(): void
    {
        $user = User::factory()->create();
        $order = $this->paystackOrderFor($user, ['payment_status' => PaymentStatus::Unpaid]);

        $this->actingAs($user)->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders.success', $order));

        $this->assertSame('cancelled', $order->fresh()->status->value);
    }

    public function test_a_paid_order_cannot_be_cancelled_by_the_customer(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->paid()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('orders.cancel', $order))->assertSessionHas('error');
        $this->assertSame('paid', $order->fresh()->status->value);
    }

    public function test_a_stranger_cannot_cancel_someone_elses_order(): void
    {
        $order = $this->paystackOrderFor(User::factory()->create());

        $this->actingAs(User::factory()->create())->post(route('orders.cancel', $order))->assertForbidden();
    }
}
