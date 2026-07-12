<?php

use App\Modules\Operations\Transfers\Http\TransferController;
use Illuminate\Support\Facades\Route;

/*
 * Stock transfers — ops.transfers module (depends on ops.inventory_ledger).
 * Route names are `admin.stock-transfers.*` to avoid clashing with the payment
 * bank-transfer reconciliation screens at `admin.transfers.*`.
 */
Route::middleware(['auth:admin', 'admin.active', 'admin.activity', 'module:ops.transfers', 'permission:manage_transfers,admin'])
    ->group(function (): void {
        Route::get('stock-transfers', [TransferController::class, 'index'])->name('stock-transfers.index');
    });
