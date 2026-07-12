<?php

namespace App\Admin;

use App\Admin\Listeners\RecordAdminAuthActivity;
use App\Support\Commerce\CommerceModules;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Boots the isolated admin domain: its own route file, URL prefix and
 * route-name prefix. Commerce Core admin routes live in routes/admin.php;
 * optional Commerce Operations modules add their routes only while enabled.
 */
class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                $this->loadRoutesFrom(base_path('routes/admin.php'));
                $this->loadModuleRoutes();
            });

        // Login history → audit log (admin guard only).
        Event::subscribe(RecordAdminAuthActivity::class);
    }

    /**
     * Load the admin route file of every BUILT Commerce Operations module (its
     * route file exists on disk). Each such file self-guards with the `module:`
     * middleware, so a disabled module 404s at request time — enable/disable is a
     * runtime toggle, no reboot or route-cache rebuild needed in normal operation.
     */
    private function loadModuleRoutes(): void
    {
        foreach ($this->app->make(CommerceModules::class)->definitions() as $definition) {
            $file = $definition['routes'] ?? null;

            if ($file && is_file($path = app_path($file))) {
                $this->loadRoutesFrom($path);
            }
        }
    }
}
