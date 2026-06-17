<?php

use App\Modules\Payment\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');

// Server-to-server webhook — CSRF-exempt (see bootstrap/app.php).
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
