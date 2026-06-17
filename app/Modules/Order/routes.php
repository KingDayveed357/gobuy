<?php

use App\Modules\Order\Http\Controllers\CheckoutController;
use App\Modules\Order\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::controller(CheckoutController::class)->group(function (): void {
    Route::get('/checkout', 'show')->name('checkout.show');
    Route::post('/checkout', 'store')->name('checkout.store');
});

Route::controller(OrderController::class)->group(function (): void {
    Route::get('/track', 'trackForm')->name('orders.track.form');
    Route::post('/track', 'track')->name('orders.track');
    Route::get('/orders/{order}/success', 'success')->name('orders.success');
});
