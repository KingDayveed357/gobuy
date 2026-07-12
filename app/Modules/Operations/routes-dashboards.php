<?php

use App\Modules\Operations\Dashboards\Http\OperationsDashboardController;
use Illuminate\Support\Facades\Route;

/*
 * Operations dashboards — ops.dashboards module. Self-guards with
 * `module:ops.dashboards`.
 */
Route::middleware(['auth:admin', 'admin.active', 'admin.activity', 'module:ops.dashboards', 'permission:view_ops_reports,admin'])
    ->group(function (): void {
        Route::get('ops-dashboard', [OperationsDashboardController::class, 'index'])->name('ops-dashboard.index');
    });
