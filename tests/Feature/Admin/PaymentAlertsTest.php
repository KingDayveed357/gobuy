<?php

namespace Tests\Feature\Admin;

use App\Admin\Notifications\AdminAlertNotification;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Critical payment/security signals must reach admins, not just the log.
 */
class PaymentAlertsTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_amount_mismatch_alerts_payment_admins(): void
    {
        Notification::fake();
        $admin = $this->actingAsAdmin('Super Admin');

        $order = Order::factory()->create(['status' => OrderStatus::Pending, 'payment_status' => PaymentStatus::Unpaid, 'total' => Money::fromNaira(5000)]);
        $order->payment()->create(['reference' => 'GB-MM', 'amount' => Money::fromNaira(5000), 'status' => 'pending']);

        // Gateway reports a different amount than we recorded → tamper signal.
        Http::fake(['*/transaction/verify/*' => Http::response([
            'status' => true, 'data' => ['status' => 'success', 'amount' => 999, 'currency' => 'NGN'],
        ])]);

        $this->assertFalse(app(PaymentService::class)->verifyAndComplete('GB-MM'));
        Notification::assertSentTo($admin, AdminAlertNotification::class,
            fn (AdminAlertNotification $n) => $n->severity === 'critical');
    }

    public function test_invalid_webhook_signature_alerts_payment_admins_once(): void
    {
        Cache::forget('webhook-sig-alert');
        Notification::fake();
        $admin = $this->actingAsAdmin('Super Admin');

        $body = json_encode(['event' => 'charge.success', 'data' => []]);
        $call = fn () => $this->call('POST', '/payment/webhook', [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => 'wrong', 'CONTENT_TYPE' => 'application/json',
        ], $body);

        $call()->assertStatus(401);
        $call()->assertStatus(401); // second forged request within the window

        // Alerted, but throttled to once per window.
        Notification::assertSentToTimes($admin, AdminAlertNotification::class, 1);
    }
}
