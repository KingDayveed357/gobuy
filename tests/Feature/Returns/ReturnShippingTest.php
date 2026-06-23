<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Returns\Enums\ReturnReason;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ReturnShippingTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    private function requestedReturn(array $attrs = []): ReturnRequest
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        return ReturnRequest::factory()->create(array_merge([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => ReturnStatus::Requested,
        ], $attrs));
    }

    public function test_approving_issues_a_label_and_moves_to_awaiting_shipment(): void
    {
        $this->actingAsAdmin('Super Admin');
        $return = $this->requestedReturn(['return_shipping_payer' => 'customer']);

        $this->post(route('admin.returns.approve', $return))->assertRedirect();

        $return->refresh();
        $this->assertSame('awaiting_shipment', $return->status->value);
        $this->assertNotNull($return->returnShipment);
        $this->assertStringStartsWith('RWB-', $return->returnShipment->tracking_reference);
        $this->assertSame('customer', $return->returnShipment->payer);
    }

    public function test_a_merchant_fault_return_gets_a_prepaid_label(): void
    {
        $this->actingAsAdmin('Super Admin');
        // reason_code damaged → return_shipping_payer is set to merchant at request time;
        // here we set it directly to assert the label carries it.
        $return = $this->requestedReturn(['reason_code' => ReturnReason::Damaged->value, 'return_shipping_payer' => 'merchant']);

        $this->post(route('admin.returns.approve', $return));

        $this->assertTrue($return->fresh()->returnShipment->isMerchantPaid());
    }

    public function test_customer_can_mark_a_return_as_shipped(): void
    {
        $this->actingAsAdmin('Super Admin');
        $return = $this->requestedReturn();
        $this->post(route('admin.returns.approve', $return));
        $return->refresh();

        $this->actingAs($return->user)
            ->post(route('account.returns.shipped', $return))
            ->assertRedirect();

        $return->refresh();
        $this->assertSame('in_transit', $return->status->value);
        $this->assertNotNull($return->returnShipment->shipped_at);
    }

    public function test_customer_can_view_their_return_label(): void
    {
        $this->actingAsAdmin('Super Admin');
        $return = $this->requestedReturn();
        $this->post(route('admin.returns.approve', $return));
        $return->refresh();

        $this->actingAs($return->user)
            ->get(route('account.returns.label', $return))
            ->assertOk()
            ->assertSee($return->returnShipment->tracking_reference);
    }

    public function test_receiving_stamps_the_shipment(): void
    {
        $this->actingAsAdmin('Super Admin');
        $return = $this->requestedReturn();
        $this->post(route('admin.returns.approve', $return));

        $this->post(route('admin.returns.receive', $return->fresh()))->assertRedirect();

        $this->assertSame('received', $return->fresh()->status->value);
        $this->assertNotNull($return->fresh()->returnShipment->received_at);
    }
}
