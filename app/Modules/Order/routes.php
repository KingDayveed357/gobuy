<?php

use App\Modules\Order\Http\Controllers\CheckoutController;
use App\Modules\Order\Http\Controllers\OrderController;
use App\Modules\Order\Http\Controllers\ProformaController;
use Illuminate\Support\Facades\Route;

// Downloadable/printable proforma invoice for approved wholesale buyers.
Route::get('/proforma', [ProformaController::class, 'show'])->middleware('auth')->name('proforma.show');

Route::controller(CheckoutController::class)->group(function (): void {
    Route::get('/checkout', 'show')->name('checkout.show');
    Route::post('/checkout', 'store')->name('checkout.store');
});

Route::controller(OrderController::class)->group(function (): void {
    Route::get('/track', 'trackForm')->name('orders.track.form');
    Route::post('/track', 'track')->name('orders.track');
    Route::get('/orders/{order}/success', 'success')->name('orders.success');

    // Manual bank transfer: instructions + proof of payment upload.
    Route::get('/orders/{order}/transfer', 'transferShow')->name('orders.transfer.show');
    Route::post('/orders/{order}/transfer', 'transferStore')->name('orders.transfer.store');
});
