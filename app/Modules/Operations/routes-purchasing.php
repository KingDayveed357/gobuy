<?php

use App\Modules\Operations\Purchasing\Http\PurchaseOrderController;
use App\Modules\Operations\Purchasing\Http\SupplierController;
use Illuminate\Support\Facades\Route;

/*
 * Suppliers, purchase orders & receiving — ops.purchasing module (depends on
 * ops.inventory_ledger). Self-guards with `module:ops.purchasing`.
 */
Route::middleware(['auth:admin', 'admin.active', 'admin.activity', 'module:ops.purchasing', 'permission:manage_purchasing,admin'])
    ->group(function (): void {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');

        Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('purchase-orders/{purchaseOrder}/place', [PurchaseOrderController::class, 'place'])->name('purchase-orders.place');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    });
