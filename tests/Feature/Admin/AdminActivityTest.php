<?php

namespace Tests\Feature\Admin;

use App\Admin\Models\Admin;
use App\Admin\Models\AdminActivity;
use App\Admin\Services\AdminActivityLogger;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Models\Order;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Phase B — immutable admin audit log + login history.
 */
class AdminActivityTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_a_successful_login_is_recorded(): void
    {
        $admin = Admin::factory()->create(['password' => Hash::make('secret'), 'is_active' => true]);

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'secret']);

        $this->assertDatabaseHas('admin_activities', ['event' => 'auth.login', 'admin_id' => $admin->id]);
    }

    public function test_a_failed_login_is_recorded_with_the_attempted_email(): void
    {
        $admin = Admin::factory()->create(['password' => Hash::make('secret')]);

        $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'wrong']);

        $activity = AdminActivity::where('event', 'auth.failed')->firstOrFail();
        $this->assertNull($activity->admin_id);
        $this->assertSame($admin->email, $activity->properties['email']);
    }

    public function test_a_mutating_admin_action_is_recorded_with_actor_and_subject(): void
    {
        $admin = $this->actingAsAdmin('Admin');

        $product = Product::factory()->stock(3)->create();
        $order = Order::factory()->create(['total' => Money::fromNaira(5000)]);
        $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id, 'name' => $product->name,
            'sku' => $product->primaryVariant()->sku, 'unit_price' => Money::fromNaira(5000),
            'quantity' => 1, 'line_total' => Money::fromNaira(5000),
        ]);
        $payment = $order->payment()->create(['reference' => 'GB-ACT', 'amount' => $order->total, 'status' => 'pending']);

        $this->post(route('admin.payments.mark-failed', $payment))->assertRedirect();

        $this->assertDatabaseHas('admin_activities', [
            'event' => 'admin.payments.mark-failed',
            'admin_id' => $admin->id,
            'subject_type' => $payment->getMorphClass(),
            'subject_id' => $payment->id,
        ]);
    }

    public function test_read_requests_are_not_recorded(): void
    {
        $this->actingAsAdmin('Admin');

        $this->get(route('admin.payments.index'))->assertOk();

        $this->assertDatabaseCount('admin_activities', 0);
    }

    public function test_the_activity_log_is_immutable(): void
    {
        $activity = app(AdminActivityLogger::class)->record('test.event');

        $this->expectException(RuntimeException::class);
        $activity->update(['event' => 'tampered']);
    }
}
