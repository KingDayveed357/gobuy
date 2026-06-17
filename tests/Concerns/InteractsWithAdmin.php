<?php

namespace Tests\Concerns;

use App\Admin\Database\Seeders\AdminAccessSeeder;
use App\Admin\Models\Admin;
use Spatie\Permission\PermissionRegistrar;

trait InteractsWithAdmin
{
    protected function seedAdminAccess(): void
    {
        $this->seed(AdminAccessSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function adminWithRole(string $role): Admin
    {
        $admin = Admin::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }

    /**
     * Seed RBAC, create an admin with the given role and authenticate as them.
     */
    protected function actingAsAdmin(string $role = 'Super Admin'): Admin
    {
        $this->seedAdminAccess();
        $admin = $this->adminWithRole($role);
        $this->actingAs($admin, 'admin');

        return $admin;
    }
}
