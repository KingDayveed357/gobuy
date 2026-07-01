<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\Payment;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin reconciliation actions on the payments page: manually resolving a
 * pending payment to paid (completing the order) or failed.
 */
class PaymentActionsTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    /**
     * @return array{order: Order, payment: Payment, variant_id: int}
     */
    private function pendingOrderWithPayment(int $stock = 5, int $qty = 2): array
    {
        $product = Product::factory()->stock($stock)->create();
        $variant = $product->primaryVariant();

        $order = Order::factory()->create(['total' => Money::fromNaira(5000)]); // factory default: pending / unpaid
        $order->items()->create([
            'product_variant_id' => $variant->id, 'name' => $product->name, 'sku' => $variant->sku,
            'unit_price' => Money::fromNaira(2500), 'quantity' => $qty, 'line_total' => Money::fromNaira(2500 * $qty),
        ]);
        $payment = $order->payment()->create([
            'reference' => 'GB-PAY-PENDING', 'amount' => $order->total, 'status' => 'pending',
        ]);

        return ['order' => $order, 'payment' => $payment, 'variant_id' => $variant->id];
    }

    public function test_admin_can_mark_a_pending_payment_as_paid_and_complete_the_order(): void
    {
        $this->actingAsAdmin('Admin');
        ['order' => $order, 'payment' => $payment, 'variant_id' => $variantId] = $this->pendingOrderWithPayment(stock: 5, qty: 2);

        $this->post(route('admin.payments.mark-paid', $payment))->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'success']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid', 'payment_status' => 'paid']);
        $this->assertSame(3, ProductVariant::find($variantId)->stock); // 5 − 2 committed
    }

    public function test_marking_a_payment_failed_also_cancels_the_order(): void
    {
        $this->actingAsAdmin('Admin');
        ['order' => $order, 'payment' => $payment, 'variant_id' => $variantId] = $this->pendingOrderWithPayment();

        $this->post(route('admin.payments.mark-failed', $payment))->assertRedirect();

        // Payment and order resolve together — never a "failed payment / pending order" half-state.
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'failed', 'status' => 'cancelled']);
        $this->assertSame(5, ProductVariant::find($variantId)->stock); // untouched
    }

    public function test_customer_tracking_shows_payment_failed_and_cancelled(): void
    {
        $this->actingAsAdmin('Admin');
        ['order' => $order, 'payment' => $payment] = $this->pendingOrderWithPayment();
        $this->post(route('admin.payments.mark-failed', $payment));

        $this->post(route('orders.track'), [
            'order_number' => $order->order_number,
            'email' => $order->customer_email,
        ])
            ->assertOk()
            ->assertSee('Payment failed')
            ->assertSee('Order cancelled')
            ->assertDontSee('Payment confirmed');
    }

    public function test_a_non_pending_payment_cannot_be_manually_resolved(): void
    {
        $this->actingAsAdmin('Admin');
        ['payment' => $payment] = $this->pendingOrderWithPayment();
        $payment->update(['status' => 'success']);

        $this->post(route('admin.payments.mark-failed', $payment))->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'success']); // unchanged
    }

    public function test_payment_actions_require_the_manage_payments_permission(): void
    {
        $this->actingAsAdmin('Support'); // a role without manage_payments
        ['payment' => $payment] = $this->pendingOrderWithPayment();

        $this->post(route('admin.payments.mark-paid', $payment))->assertForbidden();
    }
}
