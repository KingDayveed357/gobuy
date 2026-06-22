<?php

use App\Modules\Marketing\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/account/wishlist', [WishlistController::class, 'index'])->name('account.wishlist');
    Route::post('/wishlist/{product}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/wishlist/{product}/to-cart', [WishlistController::class, 'toCart'])->name('wishlist.to-cart');
    // Merge a guest's localStorage wishlist into their account after sign-in.
    Route::post('/wishlist/merge', [WishlistController::class, 'merge'])->name('wishlist.merge');
});
