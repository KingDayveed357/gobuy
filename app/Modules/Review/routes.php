<?php

use App\Modules\Review\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});
