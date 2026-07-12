<?php

use App\Modules\Operations\StockCounts\Http\StockCountController;
use Illuminate\Support\Facades\Route;

/*
 * Stock counts & damage — ops.stock_counts module (depends on ops.inventory_ledger).
 * Self-guards with `module:ops.stock_counts`.
 */
Route::middleware(['auth:admin', 'admin.active', 'admin.activity', 'module:ops.stock_counts', 'permission:manage_stock_counts,admin'])
    ->group(function (): void {
        Route::get('stock-counts', [StockCountController::class, 'index'])->name('stock-counts.index');
    });
