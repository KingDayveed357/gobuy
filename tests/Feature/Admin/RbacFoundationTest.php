<?php

namespace Tests\Feature\Admin;

use App\Admin\Database\Seeders\AdminAccessSeeder;
use App\Admin\Models\Admin;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Phase A — RBAC foundations: immutable Super Admin (Gate::before), declarative
 * catalog seeding, and the staff-lifecycle status accessor.
 */
class RbacFoundationTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_super_admin_bypasses_a_permission_it_was_never_granted(): void
    {
        $this->seedAdminAccess();
        Permission::firstOrCreate(['name' => 'manage_secret_feature', 'guard_name' => 'admin']);

        $this->assertTrue($this->adminWithRole('Super Admin')->can('manage_secret_feature'));
        $this->assertFalse($this->adminWithRole('Support')->can('manage_secret_feature'));
    }

    public function test_super_admin_reaches_a_gated_route_without_the_explicit_permission(): void
    {
        // The Super Admin role is seeded with NO explicit permissions, so this
        // passes ONLY because Gate::before grants the owner everything.
        $this->actingAsAdmin('Super Admin');

        $this->assertEmpty(Role::findByName('Super Admin', 'admin')->permissions);
        $this->get(route('admin.payments.index'))->assertOk();
    }

    public function test_is_super_admin_reflects_the_role(): void
    {
        $this->seedAdminAccess();

        $this->assertTrue($this->adminWithRole('Super Admin')->isSuperAdmin());
        $this->assertFalse($this->adminWithRole('Manager')->isSuperAdmin());
    }

    public function test_lifecycle_status_is_derived_from_columns(): void
    {
        $this->assertSame('active', Admin::factory()->create()->status());
        $this->assertSame('invited', Admin::factory()->create(['invited_at' => now(), 'last_login_at' => null])->status());
        $this->assertSame('suspended', Admin::factory()->create(['is_active' => false])->status());

        $archived = Admin::factory()->create();
        $archived->delete();
        $this->assertSame('archived', $archived->status());
    }

    public function test_seeder_syncs_the_catalog_and_is_idempotent(): void
    {
        $this->seed(AdminAccessSeeder::class);
        $this->seed(AdminAccessSeeder::class); // re-run must not duplicate or throw

        $this->assertTrue(Permission::where(['name' => 'manage_payments', 'guard_name' => 'admin'])->exists());
        $this->assertTrue(Role::where(['name' => 'Inventory Manager', 'is_system' => true])->exists());
        $this->assertSame(1, Role::where('name', 'Super Admin')->count());
        $this->assertDatabaseHas('admins', ['email' => 'admin@gobuy.test']);
    }
}
