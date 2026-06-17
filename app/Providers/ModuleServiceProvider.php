<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires every module into the application.
 *
 * Each module exposes its routes via two optional files:
 *   - routes.php  → storefront (web) routes
 *   - admin.php   → admin routes (prefixed `/admin`, named `admin.`)
 *
 * Modular routing without scattering route files across the framework.
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Storefront (customer-facing) routes only. The admin domain is
        // wired separately by App\Admin\AdminServiceProvider.
        foreach (glob(app_path('Modules/*/routes.php')) as $file) {
            Route::middleware('web')->group($file);
        }
    }
}
