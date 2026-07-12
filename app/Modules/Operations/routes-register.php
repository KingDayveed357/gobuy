<?php

use App\Modules\Operations\Register\Http\RegisterController;
use Illuminate\Support\Facades\Route;

/*
 * Cash register / day close — ops.register module (depends on ops.walk_in).
 *
 * Self-guards with `module:ops.register` so it 404s when the module is off.
 */
Route::middleware(['auth:admin', 'admin.active', 'admin.activity', 'module:ops.register', 'permission:manage_register,admin'])
    ->group(function (): void {
        Route::get('register', [RegisterController::class, 'index'])->name('register.index');
    });
