<?php

namespace Tests\Feature\Admin;

use App\Admin\Models\Admin;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Phase D — owner-only role management: create/edit/clone/delete reusable roles,
 * with the Super Admin role and assigned roles protected.
 */
class RoleManagementTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_role_management_is_owner_only(): void
    {
        $this->actingAsAdmin('Admin'); // operational admin, not the owner

        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->post(route('admin.roles.store'), ['name' => 'Hacker'])->assertForbidden();
    }

    public function test_owner_can_view_the_role_screens(): void
    {
        $this->actingAsAdmin('Super Admin');

        $this->get(route('admin.roles.index'))->assertOk()->assertSee('Inventory Manager');
        $this->get(route('admin.roles.create'))->assertOk()->assertSee('What can this role do?');
        $this->get(route('admin.roles.edit', Role::findByName('Inventory Manager', 'admin')))
            ->assertOk()->assertSee('Manage products, inventory, categories &amp; promotions', false);
    }

    public function test_owner_can_create_a_role_with_permissions(): void
    {
        $this->actingAsAdmin('Super Admin');

        $this->post(route('admin.roles.store'), [
            'name' => 'Warehouse Lead',
            'permissions' => ['manage_products', 'manage_orders'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('Warehouse Lead', 'admin');
        $this->assertEqualsCanonicalizing(['manage_products', 'manage_orders'], $role->permissions->pluck('name')->all());
    }

    public function test_owner_can_update_and_clone_a_role(): void
    {
        $this->actingAsAdmin('Super Admin');
        $role = Role::findByName('Inventory Manager', 'admin'); // seeded: manage_products

        $this->put(route('admin.roles.update', $role), ['name' => 'Inventory Manager', 'permissions' => ['manage_products', 'view_analytics']])
            ->assertRedirect(route('admin.roles.index'));
        $this->assertEqualsCanonicalizing(['manage_products', 'view_analytics'], $role->fresh()->permissions->pluck('name')->all());

        $this->post(route('admin.roles.clone', $role))->assertRedirect();
        $clone = Role::findByName('Inventory Manager (copy)', 'admin');
        $this->assertEqualsCanonicalizing($role->permissions->pluck('name')->all(), $clone->permissions->pluck('name')->all());
    }

    public function test_the_super_admin_role_cannot_be_edited_or_deleted(): void
    {
        $this->actingAsAdmin('Super Admin');
        $owner = Role::findByName('Super Admin', 'admin');

        $this->get(route('admin.roles.edit', $owner))->assertRedirect(route('admin.roles.index'));
        $this->put(route('admin.roles.update', $owner), ['name' => 'Hijacked'])->assertRedirect(route('admin.roles.index'));
        $this->delete(route('admin.roles.destroy', $owner));

        $this->assertTrue(Role::where('name', 'Super Admin')->exists());
    }

    public function test_a_role_with_staff_cannot_be_deleted_until_reassigned(): void
    {
        $this->actingAsAdmin('Super Admin');
        $role = Role::create(['name' => 'Temp Role', 'guard_name' => 'admin']);
        $staff = Admin::factory()->create();
        $staff->assignRole($role);

        $this->delete(route('admin.roles.destroy', $role))->assertSessionHas('error');
        $this->assertTrue(Role::where('name', 'Temp Role')->exists());

        $staff->removeRole($role);
        $this->delete(route('admin.roles.destroy', $role))->assertRedirect(route('admin.roles.index'));
        $this->assertFalse(Role::where('name', 'Temp Role')->exists());
    }

    public function test_unknown_permissions_are_rejected(): void
    {
        $this->actingAsAdmin('Super Admin');

        $this->post(route('admin.roles.store'), ['name' => 'Sneaky', 'permissions' => ['take_over_everything']])
            ->assertSessionHasErrors('permissions.0');
        $this->assertNull(Role::where('name', 'Sneaky')->first());
    }
}
