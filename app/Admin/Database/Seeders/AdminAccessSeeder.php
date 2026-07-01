<?php

namespace App\Admin\Database\Seeders;

use App\Admin\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Syncs the RBAC catalog from config/rbac.php (the single source of truth) into
 * the database and ensures the platform-owner accounts exist. Idempotent.
 */
class AdminAccessSeeder extends Seeder
{
    private const GUARD = 'admin';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Permissions — the fixed vocabulary developers declare in config.
        $permissions = collect(config('rbac.modules'))->flatMap(fn (array $perms) => array_keys($perms));
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => self::GUARD]);
        }

        // 2. Roles — seeded templates the owner can later edit, marked is_system.
        foreach (config('rbac.roles') as $roleName => $grantedPermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => self::GUARD]);
            $role->forceFill(['is_system' => true])->save();
            $role->syncPermissions($grantedPermissions);
        }

        // 3. Platform owner accounts (unrestricted via Gate::before).
        foreach ([
            ['email' => 'admin@gobuy.test', 'name' => 'Super Admin', 'password' => 'password'],
            ['email' => 'davidaniago@gmail.com', 'name' => 'David Aniago', 'password' => 'gobuy@test'],
        ] as $owner) {
            Admin::firstOrCreate(
                ['email' => $owner['email']],
                ['name' => $owner['name'], 'password' => Hash::make($owner['password']), 'is_active' => true],
            )->syncRoles([config('rbac.super_admin_role')]);
        }
    }
}
