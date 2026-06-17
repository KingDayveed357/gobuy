<?php

use App\Admin\Controllers\AnalyticsController;
use App\Admin\Controllers\Auth\LoginController;
use App\Admin\Controllers\CategoryController;
use App\Admin\Controllers\CustomerController;
use App\Admin\Controllers\DashboardController;
use App\Admin\Controllers\NotificationController;
use App\Admin\Controllers\OrderController;
use App\Admin\Controllers\PaymentController;
use App\Admin\Controllers\ProductController;
use App\Admin\Controllers\WholesaleController;
use Illuminate\Support\Facades\Route;

// Guest (unauthenticated admin) routes.
Route::middleware('guest:admin')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

// Authenticated admin area.
Route::middleware(['auth:admin', 'admin.active'])->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::middleware('permission:manage_products,admin')->group(function (): void {
        Route::resource('products', ProductController::class)->except('show');

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::middleware('permission:manage_orders,admin')->group(function (): void {
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    });

    Route::middleware('permission:manage_customers,admin')->group(function (): void {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('wholesale', [WholesaleController::class, 'index'])->name('wholesale.index');
        Route::post('wholesale/{user}/approve', [WholesaleController::class, 'approve'])->name('wholesale.approve');
        Route::post('wholesale/{user}/reject', [WholesaleController::class, 'reject'])->name('wholesale.reject');
    });

    Route::middleware('permission:manage_payments,admin')->group(function (): void {
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('orders/{order}/refund', [PaymentController::class, 'refund'])->name('orders.refund');
    });

    Route::middleware('permission:view_analytics,admin')->group(function (): void {
        Route::get('analytics', AnalyticsController::class)->name('analytics');
    });

    // Notifications are available to every authenticated admin.
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');
});
