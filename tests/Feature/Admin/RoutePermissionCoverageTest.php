<?php

namespace Tests\Feature\Admin;

use App\Admin\Models\Admin;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Phase C — guards the permission reshape. The coverage test makes it impossible
 * to ship an admin route that is ungated or references an unknown permission, so
 * the finance carve-outs (and future changes) can't accidentally lock out or
 * expose a route.
 */
class RoutePermissionCoverageTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    /**
     * Routes any authenticated admin may reach without a specific permission.
     *
     * @var list<string>
     */
    private const BASELINE = [
        'admin.dashboard',
        'admin.logout',
        'admin.settings',
        'admin.settings.profile',
        'admin.settings.security',
        'admin.settings.two-factor', // personal 2FA toggle
        'admin.notifications.index', // the admin's own notification bell
        'admin.notifications.read',
        // Pre-auth 2FA challenge (session-guarded, like login).
        'admin.2fa.challenge',
        'admin.2fa.verify',
        'admin.2fa.resend',
    ];

    public function test_every_admin_route_is_gated_and_references_a_known_permission(): void
    {
        $catalog = collect(config('rbac.modules'))->flatMap(fn (array $perms) => array_keys($perms))->all();

        $ungated = [];
        $unknownPermission = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            // Skip the guest login routes (GET 'admin.login', POST 'admin.').
            if ($name === null || ! str_starts_with($name, 'admin.') || in_array($name, ['admin.login', 'admin.'], true)) {
                continue;
            }
            if (in_array($name, self::BASELINE, true)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            // A route is protected by an owner gate, a URL signature (public signed
            // links like staff activation), or a specific permission.
            $isOwnerOnly = in_array('super_admin', $middleware, true);
            $isSigned = in_array('signed', $middleware, true);
            $permissionMiddleware = collect($middleware)
                ->first(fn ($m) => is_string($m) && str_starts_with($m, 'permission:'));

            if (! $isOwnerOnly && ! $isSigned && $permissionMiddleware === null) {
                $ungated[] = $name;

                continue;
            }

            if ($permissionMiddleware !== null) {
                $permission = explode(',', substr($permissionMiddleware, strlen('permission:')))[0];
                if (! in_array($permission, $catalog, true)) {
                    $unknownPermission[] = "{$name} → {$permission}";
                }
            }
        }

        $this->assertSame([], $ungated, 'Ungated admin routes: '.implode(', ', $ungated));
        $this->assertSame([], $unknownPermission, 'Admin routes referencing an unknown permission: '.implode(', ', $unknownPermission));
    }

    public function test_store_settings_are_owner_only(): void
    {
        $this->actingAsAdmin('Admin'); // operational admin, not the owner
        $this->get(route('admin.settings.store'))->assertForbidden();

        $this->actingAsAdmin('Super Admin');
        $this->get(route('admin.settings.store'))->assertOk();
    }

    public function test_issuing_a_refund_requires_the_refunds_permission_not_just_payments(): void
    {
        $this->seedAdminAccess();

        $role = Role::firstOrCreate(['name' => 'Payments Only', 'guard_name' => 'admin']);
        $role->syncPermissions(['manage_payments']);
        $admin = Admin::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        $order = Order::factory()->paid()->create();

        // Can reconcile payments, but moving money out needs manage_refunds.
        $this->get(route('admin.payments.index'))->assertOk();
        $this->post(route('admin.orders.refund', $order))->assertForbidden();
    }
}
