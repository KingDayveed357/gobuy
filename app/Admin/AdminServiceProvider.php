<?php

namespace App\Admin;

use App\Admin\Listeners\RecordAdminAuthActivity;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Boots the isolated admin domain: its own route file, URL prefix and
 * route-name prefix. All admin routes live in routes/admin.php behind the
 * `admin` auth guard.
 */
class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/admin.php'));

        // Login history → audit log (admin guard only).
        Event::subscribe(RecordAdminAuthActivity::class);
    }
}
