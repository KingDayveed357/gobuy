<?php

use App\Modules\Operations\Packaging\Http\PackagingController;
use Illuminate\Support\Facades\Route;

/*
 * Packaging units — ops.packaging module. Self-guards with `module:ops.packaging`.
 */
Route::middleware(['auth:admin', 'admin.active', 'admin.activity', 'module:ops.packaging', 'permission:manage_packaging,admin'])
    ->group(function (): void {
        Route::get('packaging', [PackagingController::class, 'index'])->name('packaging.index');
    });
