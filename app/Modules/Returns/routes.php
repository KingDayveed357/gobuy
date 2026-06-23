<?php

use App\Modules\Returns\Http\Controllers\ReturnController;
use App\Modules\Returns\Http\Controllers\ReturnPhotoController;
use Illuminate\Support\Facades\Route;

// Gated photo stream — authorizes the owning customer OR a returns admin in the
// controller (so both guards work); not in the web-auth group.
Route::get('/returns/photo/{return}/{media}', [ReturnPhotoController::class, 'show'])->name('returns.photo');

Route::middleware('auth')->group(function (): void {
    Route::get('/account/returns', [ReturnController::class, 'index'])->name('account.returns.index');
    Route::get('/account/returns/{return}', [ReturnController::class, 'show'])->name('account.returns.show');
    Route::get('/account/returns/{return}/label', [ReturnController::class, 'label'])->name('account.returns.label');
    Route::post('/account/returns/{return}/shipped', [ReturnController::class, 'markShipped'])->name('account.returns.shipped');
    Route::post('/account/returns/{return}/reply', [ReturnController::class, 'reply'])->name('account.returns.reply');
    Route::post('/account/returns/{return}/cancel', [ReturnController::class, 'cancel'])->name('account.returns.cancel');

    Route::get('/account/orders/{order}/returns/create', [ReturnController::class, 'create'])->name('account.returns.create');
    Route::post('/account/orders/{order}/returns', [ReturnController::class, 'store'])
        ->middleware('throttle:10,1') // abuse guard: 10 return submissions/minute/user
        ->name('account.returns.store');
});
