<?php

namespace Tests\Feature\Admin;

use App\Admin\Models\Admin;
use App\Admin\Notifications\SecurityAlertNotification;
use App\Admin\Services\AdminActivityLogger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Phase G — owner-only activity dashboard, plus the Phase F owner security alert.
 */
class ActivityLogTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_activity_log_is_owner_only(): void
    {
        $this->actingAsAdmin('Admin');
        $this->get(route('admin.activity.index'))->assertForbidden();
    }

    public function test_owner_can_view_activity_and_filter_to_logins(): void
    {
        $this->actingAsAdmin('Super Admin');
        $logger = app(AdminActivityLogger::class);
        $logger->record('auth.login', null, null, 'Ada signed in');
        $logger->record('admin.orders.status', null, null, 'Updated an order');

        $this->get(route('admin.activity.index'))->assertOk()
            ->assertSee('Updated an order')->assertSee('Ada signed in');

        $this->get(route('admin.activity.index', ['view' => 'logins']))->assertOk()
            ->assertSee('Ada signed in')->assertDontSee('Updated an order');
    }

    public function test_a_sensitive_staff_action_alerts_the_other_owners(): void
    {
        Mail::fake();
        Notification::fake();

        $actor = $this->actingAsAdmin('Super Admin');
        $coOwner = Admin::factory()->create();
        $coOwner->assignRole('Super Admin');

        $this->post(route('admin.staff.store'), [
            'name' => 'New Hire', 'email' => 'hire@gobuy.test', 'role' => 'Support',
        ]);

        Notification::assertSentTo($coOwner, SecurityAlertNotification::class);
        Notification::assertNotSentTo($actor, SecurityAlertNotification::class); // never the actor
    }
}
