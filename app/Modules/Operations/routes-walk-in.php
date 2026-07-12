<?php

use App\Modules\Operations\WalkIn\Http\WalkInController;
use Illuminate\Support\Facades\Route;

/*
 * Walk-in / in-store sales — ops.walk_in module.
 *
 * Loaded (always, for built modules) inside the admin group by
 * AdminServiceProvider, but every route self-guards with `module:ops.walk_in`
 * so it 404s the instant the module is switched off. Auth + permission are
 * applied here because module route files sit outside routes/admin.php's group.
 */
Route::middleware(['auth:admin', 'admin.active', 'admin.activity', 'module:ops.walk_in', 'permission:manage_walk_in_sales,admin'])
    ->group(function (): void {
        Route::get('walk-in', [WalkInController::class, 'index'])->name('walk-in.index');
    });
