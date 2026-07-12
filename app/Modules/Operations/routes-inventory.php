<?php

use App\Modules\Operations\Inventory\Http\InventoryLocationController;
use Illuminate\Support\Facades\Route;

/*
 * Multi-location inventory — ops.inventory_ledger module (locations UI over the
 * always-on CO-1 ledger substrate). Self-guards with `module:ops.inventory_ledger`.
 */
Route::middleware(['auth:admin', 'admin.active', 'admin.activity', 'module:ops.inventory_ledger', 'permission:manage_inventory_ops,admin'])
    ->group(function (): void {
        Route::get('stock-locations', [InventoryLocationController::class, 'index'])->name('stock-locations.index');
        Route::post('stock-locations', [InventoryLocationController::class, 'store'])->name('stock-locations.store');
        Route::get('stock-locations/{location}', [InventoryLocationController::class, 'show'])->name('stock-locations.show');
        Route::put('stock-locations/{location}', [InventoryLocationController::class, 'update'])->name('stock-locations.update');
    });
