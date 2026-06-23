<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Notification\Notifications\ReturnStatusMessage;
use App\Modules\Order\Models\Order;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Models\ReturnRequest;
use App\Modules\Returns\Services\ReturnRequestService;
use App\Modules\Returns\StateMachines\ReturnStateMachine;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ReturnNotificationTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    private function pendingReturn(): ReturnRequest
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'customer_phone' => '08030001122']);

        return ReturnRequest::factory()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => ReturnStatus::Requested,
        ]);
    }

    public function test_customer_is_notified_when_a_return_is_approved(): void
    {
        Notification::fake();
        $return = $this->pendingReturn();

        // Approve via the state machine path (label issued → awaiting_shipment).
        app(ReturnRequestService::class)->approve($return);

        Notification::assertSentOnDemand(ReturnStatusMessage::class);
    }

    public function test_customer_is_notified_when_a_return_is_rejected(): void
    {
        Notification::fake();
        $return = $this->pendingReturn();

        app(ReturnStateMachine::class)->transitionTo($return, ReturnStatus::Rejected, null, 'denied');

        Notification::assertSentOnDemand(ReturnStatusMessage::class);
    }

    public function test_no_message_is_sent_for_a_quiet_transition(): void
    {
        Notification::fake();
        $return = ReturnRequest::factory()->create([
            'order_id' => Order::factory()->create(['customer_phone' => '08030001122'])->id,
            'status' => ReturnStatus::Requested,
        ]);

        // Requested → Cancelled is not a customer-notify status.
        app(ReturnStateMachine::class)->transitionTo($return, ReturnStatus::Cancelled, null, 'cancelled');

        Notification::assertNothingSent();
    }
}
