<?php

namespace Tests\Feature\Admin;

use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * An order can legitimately have multiple payment references (retries). Once one
 * reference has paid the order, a redundant sibling reference must never be able
 * to corrupt the order's paid state.
 */
class MultiReferencePaymentTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    /**
     * @return array{order: Order, paid: Payment, pending: Payment}
     */
    private function paidOrderWithDanglingReference(): array
    {
        $order = Order::factory()->paid()->create();
        $paid = $order->payment()->create(['reference' => 'GB-REF-A', 'amount' => $order->total, 'status' => 'success', 'paid_at' => now()]);
        $pending = $order->payment()->create(['reference' => 'GB-REF-B', 'amount' => $order->total, 'status' => 'pending']);

        return ['order' => $order, 'paid' => $paid, 'pending' => $pending];
    }

    public function test_failing_a_redundant_reference_does_not_downgrade_a_paid_order(): void
    {
        ['order' => $order, 'pending' => $pending] = $this->paidOrderWithDanglingReference();
        $this->actingAsAdmin('Admin');

        $this->post(route('admin.payments.mark-failed', $pending))->assertRedirect();

        // The dangling attempt is failed, but the order stays fully paid.
        $this->assertDatabaseHas('payments', ['id' => $pending->id, 'status' => 'failed']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid', 'payment_status' => 'paid']);
    }

    public function test_a_second_reference_cannot_be_marked_paid_on_an_already_paid_order(): void
    {
        ['order' => $order, 'pending' => $pending] = $this->paidOrderWithDanglingReference();
        $this->actingAsAdmin('Admin');

        $this->post(route('admin.payments.mark-paid', $pending))->assertSessionHas('error');

        $this->assertDatabaseHas('payments', ['id' => $pending->id, 'status' => 'pending']); // untouched
        $this->assertSame(1, Payment::where('order_id', $order->id)->where('status', 'success')->count()); // still one success
    }

    public function test_verifying_a_redundant_reference_reports_already_paid(): void
    {
        ['pending' => $pending] = $this->paidOrderWithDanglingReference();
        $this->actingAsAdmin('Admin');

        $this->post(route('admin.payments.verify', $pending))->assertSessionHas('error');
        $this->assertDatabaseHas('payments', ['id' => $pending->id, 'status' => 'pending']);
    }

    public function test_service_refuses_to_double_pay_an_order(): void
    {
        ['pending' => $pending] = $this->paidOrderWithDanglingReference();

        app(PaymentService::class)->markPaidManually($pending->fresh());

        $this->assertSame('pending', $pending->fresh()->status); // no-op — no second success
    }
}
