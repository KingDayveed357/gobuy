<?php

namespace Tests\Feature\Admin;

use App\Admin\Models\Admin;
use App\Admin\Notifications\AdminAlertNotification;
use App\Admin\Notifications\NewPaidOrderNotification;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Guards the root-cause fix: admin in-app notifications must be created the
 * moment the business event happens, WITHOUT a queue worker running. These tests
 * flip the queue to the async `database` connection (as in production) and run no
 * worker — the notification must still land.
 */
class NotificationPipelineTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_a_new_paid_order_notifies_admins_without_a_queue_worker(): void
    {
        config(['queue.default' => 'database']); // async queue, nothing consuming it

        $this->seedAdminAccess();
        $handler = $this->adminWithRole('Manager'); // has manage_orders

        $order = Order::factory()->create(['customer_email' => 'buyer@example.com']);
        $order->items()->create([
            'product_variant_id' => null, 'name' => 'Item', 'sku' => 'I1',
            'unit_price' => 1000, 'quantity' => 1, 'line_total' => 1000,
        ]);

        app(PaymentService::class)->completeOrder($order->fresh());

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $handler->id,
            'notifiable_type' => Admin::class,
            'type' => NewPaidOrderNotification::class,
        ]);
        $this->assertSame(1, $handler->fresh()->unreadNotifications()->count());
    }

    public function test_admin_alerts_reach_the_bell_synchronously_without_a_worker(): void
    {
        config(['queue.default' => 'database']);

        $this->seedAdminAccess();
        $admin = $this->adminWithRole('Super Admin');

        Notification::send(
            Admin::withAbility('manage_payments'),
            new AdminAlertNotification('Webhook failure', 'A webhook was rejected.', 'critical', route('admin.notifications.index')),
        );

        // Despite the async default queue + no worker, the database channel is
        // delivered synchronously (viaConnections), so the bell has the row now.
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => Admin::class,
            'type' => AdminAlertNotification::class,
        ]);
    }

    public function test_the_notification_center_renders_both_orders_and_alerts(): void
    {
        $admin = $this->actingAsAdmin('Super Admin');
        $order = Order::factory()->paid()->create();

        $admin->notify(new NewPaidOrderNotification($order));
        $admin->notify(new AdminAlertNotification('Payment mismatch', 'A payment did not match.', 'critical', route('admin.notifications.index')));

        $this->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('New paid order')
            ->assertSee($order->order_number)
            ->assertSee('Payment mismatch')       // alert title now renders (was hardcoded before)
            ->assertSee('A payment did not match.'); // alert body now renders
    }
}
