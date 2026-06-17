<?php

use App\Modules\Customer\Http\Controllers\AccountController;
use App\Modules\Customer\Http\Controllers\LoginController;
use App\Modules\Customer\Http\Controllers\RegisterController;
use App\Modules\Customer\Http\Controllers\WholesaleApplicationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');

    Route::get('/account/wholesale', [WholesaleApplicationController::class, 'create'])->name('account.wholesale');
    Route::post('/account/wholesale', [WholesaleApplicationController::class, 'store']);
});
