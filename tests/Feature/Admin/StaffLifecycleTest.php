<?php

namespace Tests\Feature\Admin;

use App\Admin\Mail\StaffInvitationMail;
use App\Admin\Models\Admin;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Phase E — staff lifecycle: invite → activate → change role → suspend →
 * reactivate → replace → archive, all owner-only and guard-protected.
 */
class StaffLifecycleTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_staff_management_is_owner_only(): void
    {
        $this->actingAsAdmin('Admin');

        $this->get(route('admin.staff.index'))->assertForbidden();
        $this->post(route('admin.staff.store'), [])->assertForbidden();
    }

    public function test_owner_can_invite_staff_and_an_invitation_is_sent(): void
    {
        Mail::fake();
        $this->actingAsAdmin('Super Admin');

        $this->post(route('admin.staff.store'), [
            'name' => 'Grace Eze', 'email' => 'grace@gobuy.test', 'role' => 'Inventory Manager',
        ])->assertRedirect(route('admin.staff.index', ['status' => 'invited']));

        $staff = Admin::where('email', 'grace@gobuy.test')->firstOrFail();
        $this->assertTrue($staff->hasRole('Inventory Manager'));
        $this->assertSame('invited', $staff->status());
        Mail::assertQueued(StaffInvitationMail::class);
    }

    public function test_invited_staff_activate_via_a_signed_link(): void
    {
        $this->seedAdminAccess();
        $admin = Admin::factory()->create(['invited_at' => now(), 'last_login_at' => null]);

        $this->get(URL::temporarySignedRoute('admin.staff.activate', now()->addDay(), ['admin' => $admin->id]))
            ->assertOk()->assertSee('Activate my account');

        $this->post(URL::signedRoute('admin.staff.activate.store', ['admin' => $admin->id]), [
            'password' => 'Sup3r$ecret!', 'password_confirmation' => 'Sup3r$ecret!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin->fresh(), 'admin');
        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->assertTrue(Hash::check('Sup3r$ecret!', $admin->fresh()->password));
    }

    public function test_an_unsigned_activation_link_is_rejected(): void
    {
        $admin = Admin::factory()->create(['invited_at' => now()]);

        $this->get(route('admin.staff.activate', $admin))->assertForbidden();
    }

    public function test_owner_can_change_a_staff_members_role(): void
    {
        $this->actingAsAdmin('Super Admin');
        $staff = Admin::factory()->create();
        $staff->assignRole('Support');

        $this->put(route('admin.staff.role', $staff), ['role' => 'Inventory Manager'])->assertRedirect();

        $this->assertTrue($staff->fresh()->hasRole('Inventory Manager'));
        $this->assertFalse($staff->fresh()->hasRole('Support'));
    }

    public function test_owner_can_suspend_reactivate_and_archive(): void
    {
        $this->actingAsAdmin('Super Admin');
        $staff = Admin::factory()->create();
        $staff->assignRole('Support');

        $this->post(route('admin.staff.suspend', $staff));
        $this->assertFalse($staff->fresh()->is_active);
        $this->assertNotNull($staff->fresh()->suspended_at);

        $this->post(route('admin.staff.reactivate', $staff));
        $this->assertTrue($staff->fresh()->is_active);

        $this->delete(route('admin.staff.archive', $staff))->assertRedirect(route('admin.staff.index'));
        $this->assertSoftDeleted('admins', ['id' => $staff->id]);
    }

    public function test_replace_suspends_and_redirects_to_invite_with_the_same_role(): void
    {
        $this->actingAsAdmin('Super Admin');
        $staff = Admin::factory()->create();
        $staff->assignRole('Sales Manager');

        $this->post(route('admin.staff.replace', $staff))
            ->assertRedirect(route('admin.staff.index', ['invite' => 'Sales Manager']));

        $this->assertFalse($staff->fresh()->is_active);
    }

    public function test_owner_can_resend_an_invitation(): void
    {
        Mail::fake();
        $this->actingAsAdmin('Super Admin');
        $invited = Admin::factory()->create(['invited_at' => now(), 'last_login_at' => null]);
        $invited->assignRole('Support');

        $this->post(route('admin.staff.resend', $invited))->assertRedirect();
        Mail::assertQueued(StaffInvitationMail::class);
    }

    public function test_super_admins_and_self_are_protected_from_management(): void
    {
        $owner = $this->actingAsAdmin('Super Admin');

        $this->post(route('admin.staff.suspend', $owner))->assertForbidden(); // self

        $other = Admin::factory()->create();
        $other->assignRole('Super Admin');
        $this->delete(route('admin.staff.archive', $other))->assertForbidden();
        $this->assertNotSoftDeleted('admins', ['id' => $other->id]);
    }

    public function test_staff_cannot_be_invited_as_super_admin(): void
    {
        $this->actingAsAdmin('Super Admin');

        $this->post(route('admin.staff.store'), ['name' => 'X', 'email' => 'x@gobuy.test', 'role' => 'Super Admin'])
            ->assertSessionHasErrors('role');
        $this->assertNull(Admin::where('email', 'x@gobuy.test')->first());
    }
}
