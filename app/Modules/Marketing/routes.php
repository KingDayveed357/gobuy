<?php

use App\Modules\Marketing\Http\Controllers\PageController;
use App\Modules\Marketing\Http\Controllers\PreviewController;
use App\Modules\Marketing\Http\Controllers\TrackingController;
use App\Modules\Marketing\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/account/wishlist', [WishlistController::class, 'index'])->name('account.wishlist');
    Route::post('/wishlist/{product}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/wishlist/{product}/to-cart', [WishlistController::class, 'toCart'])->name('wishlist.to-cart');
    // Merge a guest's localStorage wishlist into their account after sign-in.
    Route::post('/wishlist/merge', [WishlistController::class, 'merge'])->name('wishlist.merge');
});

// Signed, no-login page preview (home or a landing page) — URL generated in the
// admin builder and shared for sign-off. The signature authorises + time-limits.
Route::get('/preview/{slug?}', [PreviewController::class, 'show'])
    ->middleware('signed')
    ->name('storefront.preview');

// Public landing pages — a campaign's premium destination.
Route::get('/p/{slug}', [PageController::class, 'show'])->name('storefront.page');

// Merchandising telemetry beacon (impressions/clicks) — CSRF-protected via the
// fetch header, throttled since it's an unauthenticated public write.
Route::post('/track/block', [TrackingController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('storefront.track-block');
