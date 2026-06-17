<?php

namespace Tests\Feature\Admin;

use App\Admin\Models\Admin;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_order_handlers_are_notified_when_an_order_is_paid(): void
    {
        $this->seedAdminAccess();
        $orderHandler = $this->adminWithRole('Manager');   // has manage_orders
        $support = $this->adminWithRole('Support');         // also has manage_orders

        Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success']])]);

        $order = Order::factory()->create(['customer_email' => 'buyer@example.com']);
        $order->items()->create(['product_id' => null, 'name' => 'Item', 'sku' => 'I1', 'unit_price' => 1000, 'quantity' => 1, 'line_total' => 1000]);
        $order->statusHistories()->create(['status' => OrderStatus::Pending, 'note' => 'Order placed']);
        $payment = $order->payment()->create(['reference' => 'GB-PAY-NOTIFY', 'amount' => $order->total, 'status' => 'pending']);

        app(PaymentService::class)->verifyAndComplete($payment->reference);

        $this->assertSame(1, $orderHandler->fresh()->notifications()->count());
        $this->assertSame(1, $support->fresh()->notifications()->count());
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $orderHandler->id,
            'notifiable_type' => Admin::class,
        ]);
    }

    public function test_admin_can_mark_notifications_read(): void
    {
        $admin = $this->actingAsAdmin('Super Admin');
        $order = Order::factory()->paid()->create();
        $admin->notify(new \App\Admin\Notifications\NewPaidOrderNotification($order));

        $this->assertSame(1, $admin->fresh()->unreadNotifications()->count());

        $this->post(route('admin.notifications.read'))->assertRedirect();

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }
}
