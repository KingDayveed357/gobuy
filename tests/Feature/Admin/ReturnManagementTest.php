<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ReturnManagementTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    private function pendingReturn(): ReturnRequest
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        return ReturnRequest::factory()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => ReturnStatus::Requested,
        ]);
    }

    public function test_admin_returns_queue_renders(): void
    {
        $this->actingAsAdmin('Super Admin');
        $this->pendingReturn();

        $this->get(route('admin.returns.index'))->assertOk()->assertSee('Returns');
    }

    public function test_admin_can_approve_a_return(): void
    {
        $this->actingAsAdmin('Super Admin');
        $return = $this->pendingReturn();

        $this->post(route('admin.returns.approve', $return))->assertRedirect();

        $return->refresh();
        // Approval issues the return label and asks the customer to ship it back.
        $this->assertSame('awaiting_shipment', $return->status->value);
        $this->assertNotNull($return->approved_by);
        $this->assertNotNull($return->returnShipment);
        $this->assertDatabaseHas('return_events', ['return_request_id' => $return->id, 'action' => 'approved']);
    }

    public function test_admin_can_deny_a_return(): void
    {
        $this->actingAsAdmin('Super Admin');
        $return = $this->pendingReturn();

        $this->post(route('admin.returns.deny', $return), ['note' => 'Outside policy'])->assertRedirect();

        $this->assertSame('rejected', $return->fresh()->status->value);
    }

    public function test_an_illegal_transition_is_rejected(): void
    {
        $this->actingAsAdmin('Super Admin');
        $return = ReturnRequest::factory()->status(ReturnStatus::Rejected)->create();

        // Cannot receive an already-rejected return.
        $this->post(route('admin.returns.receive', $return))->assertSessionHas('error');
        $this->assertSame('rejected', $return->fresh()->status->value);
    }

    public function test_approve_then_receive_walks_the_lifecycle(): void
    {
        $this->actingAsAdmin('Super Admin');
        $return = $this->pendingReturn();

        $this->post(route('admin.returns.approve', $return))->assertRedirect();
        $this->post(route('admin.returns.receive', $return->fresh()))->assertRedirect();

        $this->assertSame('received', $return->fresh()->status->value);
        $this->assertNotNull($return->fresh()->received_by);
    }
}
