<?php

namespace App\Admin\Database\Seeders;

use App\Admin\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminAccessSeeder extends Seeder
{
    private const GUARD = 'admin';

    /**
     * Permission => the roles that should hold it.
     *
     * @var array<string, list<string>>
     */
    private const MATRIX = [
        'manage_products' => ['Super Admin', 'Admin', 'Manager'],
        'manage_orders' => ['Super Admin', 'Admin', 'Manager', 'Support'],
        'manage_customers' => ['Super Admin', 'Admin', 'Support'],
        'manage_payments' => ['Super Admin', 'Admin'],
        'manage_returns' => ['Super Admin', 'Admin', 'Manager', 'Support'],
        'manage_refunds' => ['Super Admin', 'Admin'],
        'view_analytics' => ['Super Admin', 'Admin', 'Manager'],
        'manage_admins' => ['Super Admin'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = collect(['Super Admin', 'Admin', 'Manager', 'Support'])
            ->mapWithKeys(fn (string $name) => [
                $name => Role::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]),
            ]);

        foreach (self::MATRIX as $permission => $grantedRoles) {
            $permission = Permission::firstOrCreate(['name' => $permission, 'guard_name' => self::GUARD]);

            foreach ($grantedRoles as $roleName) {
                $roles[$roleName]->givePermissionTo($permission);
            }
        }

        $superAdmin = Admin::firstOrCreate(
            ['email' => 'admin@gobuy.test'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'is_active' => true],
        );
        $superAdmin->syncRoles(['Super Admin']);

        $david = Admin::firstOrCreate(
            ['email' => 'davidaniago@gmail.com'],
            ['name' => 'David Aniago', 'password' => Hash::make('gobuy@test'), 'is_active' => true],
        );
        $david->syncRoles(['Super Admin']);
    }
}
