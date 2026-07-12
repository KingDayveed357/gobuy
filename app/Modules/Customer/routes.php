<?php

use App\Modules\Customer\Http\Controllers\AccountController;
use App\Modules\Customer\Http\Controllers\AddressController;
use App\Modules\Customer\Http\Controllers\Auth\PasswordResetController;
use App\Modules\Customer\Http\Controllers\Auth\VerifyEmailController;
use App\Modules\Customer\Http\Controllers\LoginController;
use App\Modules\Customer\Http\Controllers\RegisterController;
use App\Modules\Customer\Http\Controllers\WholesaleApplicationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    // Password reset (token-based, via the Laravel password broker).
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Email OTP verification.
    Route::get('/email/verify', [VerifyEmailController::class, 'show'])->name('verification.notice');
    Route::post('/email/verify', [VerifyEmailController::class, 'verify'])->middleware('throttle:6,1')->name('verification.verify');
    Route::post('/email/verify/resend', [VerifyEmailController::class, 'resend'])->middleware('throttle:6,1')->name('verification.resend');

    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/account/orders/{order}/reorder/preview', [AccountController::class, 'reorderPreview'])->name('account.orders.reorder.preview');
    Route::post('/account/orders/{order}/reorder', [AccountController::class, 'reorder'])->name('account.orders.reorder');
    Route::get('/account/wallet', [AccountController::class, 'wallet'])->name('account.wallet');
    Route::get('/account/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::post('/account/settings/profile', [AccountController::class, 'updateProfile'])->name('account.settings.profile');
    Route::post('/account/settings/security', [AccountController::class, 'updateSecurity'])->name('account.settings.security');
    Route::post('/account/settings/notifications', [AccountController::class, 'updateNotifications'])->name('account.settings.notifications');

    // Address book.
    Route::get('/account/addresses', [AddressController::class, 'index'])->name('account.addresses.index');
    Route::get('/account/addresses/json', [AddressController::class, 'json'])->name('account.addresses.json');
    Route::post('/account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
    Route::put('/account/addresses/{address}', [AddressController::class, 'update'])->name('account.addresses.update');
    Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');
    Route::post('/account/addresses/{address}/default', [AddressController::class, 'setDefault'])->name('account.addresses.default');

    Route::get('/account/wholesale', [WholesaleApplicationController::class, 'create'])->name('account.wholesale');
    Route::post('/account/wholesale', [WholesaleApplicationController::class, 'store'])->middleware('verified.otp');
});
