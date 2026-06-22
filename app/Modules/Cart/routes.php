<?php

use App\Modules\Cart\Http\Controllers\CartController;
use App\Modules\Cart\Http\Controllers\CartCouponController;
use Illuminate\Support\Facades\Route;

Route::controller(CartController::class)->prefix('cart')->name('cart.')->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::post('/set-quantity', 'setQuantity')->name('set-quantity');
    Route::delete('/', 'clear')->name('clear');
    Route::patch('/items/{item}', 'update')->name('items.update');
    Route::delete('/items/{item}', 'destroy')->name('items.destroy');
});

Route::controller(CartCouponController::class)->prefix('cart/coupon')->name('cart.coupon.')->group(function (): void {
    Route::post('/', 'store')->name('apply');
    Route::delete('/', 'destroy')->name('remove');
});
