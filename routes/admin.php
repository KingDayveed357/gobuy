<?php

use App\Admin\Controllers\ActivityLogController;
use App\Admin\Controllers\AnalyticsController;
use App\Admin\Controllers\Auth\LoginController;
use App\Admin\Controllers\Auth\StaffActivationController;
use App\Admin\Controllers\Auth\TwoFactorChallengeController;
use App\Admin\Controllers\BannerController;
use App\Admin\Controllers\BrandController;
use App\Admin\Controllers\BulkPricingController;
use App\Admin\Controllers\BulkRequestController;
use App\Admin\Controllers\CategoryController;
use App\Admin\Controllers\CouponController;
use App\Admin\Controllers\CustomerController;
use App\Admin\Controllers\DashboardController;
use App\Admin\Controllers\DeliveryZoneController;
use App\Admin\Controllers\InventoryController;
use App\Admin\Controllers\InventoryImportController;
use App\Admin\Controllers\LocationController;
use App\Admin\Controllers\LogisticsController;
use App\Admin\Controllers\NotificationController;
use App\Admin\Controllers\OrderController;
use App\Admin\Controllers\PaymentController;
use App\Admin\Controllers\ProductController;
use App\Admin\Controllers\PromotionController;
use App\Admin\Controllers\QuantityDiscountController;
use App\Admin\Controllers\ReturnController;
use App\Admin\Controllers\ReviewController;
use App\Admin\Controllers\RoleController;
use App\Admin\Controllers\SettingsController;
use App\Admin\Controllers\ShipmentController;
use App\Admin\Controllers\StaffController;
use App\Admin\Controllers\StoreCreditController;
use App\Admin\Controllers\TransferReconciliationController;
use App\Admin\Controllers\WholesaleController;
use App\Modules\Notification\Push\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

// Guest (unauthenticated admin) routes.
Route::middleware('guest:admin')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

// Public staff activation — reached only via the signed link in the invite email.
Route::middleware('signed')->group(function (): void {
    Route::get('staff/activate/{admin}', [StaffActivationController::class, 'create'])->name('staff.activate');
    Route::post('staff/activate/{admin}', [StaffActivationController::class, 'store'])->name('staff.activate.store');
});

// Second step of opt-in 2FA login (guarded by a pending-admin session, not auth).
Route::get('2fa/challenge', [TwoFactorChallengeController::class, 'create'])->name('2fa.challenge');
Route::post('2fa/challenge', [TwoFactorChallengeController::class, 'store'])->name('2fa.verify')->middleware('throttle:10,1');
Route::post('2fa/resend', [TwoFactorChallengeController::class, 'resend'])->name('2fa.resend')->middleware('throttle:3,1');

// Authenticated admin area.
Route::middleware(['auth:admin', 'admin.active', 'admin.activity'])->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    // Personal account settings — any signed-in admin manages their own.
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('settings/security', [SettingsController::class, 'updateSecurity'])->name('settings.security');
    Route::post('settings/two-factor', [SettingsController::class, 'toggleTwoFactor'])->name('settings.two-factor');

    // Store / financial configuration + staff & role management — owner only.
    Route::middleware('super_admin')->group(function (): void {
        Route::get('settings/store', [SettingsController::class, 'store'])->name('settings.store');
        Route::post('settings/store', [SettingsController::class, 'updateStore'])->name('settings.store.update');

        // Roles: reusable permission templates.
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::post('roles/{role}/clone', [RoleController::class, 'clone'])->name('roles.clone');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Staff: the people, and their access lifecycle. (Invite is a slide-over
        // drawer on the directory — no dedicated page.)
        Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('staff/{admin}', [StaffController::class, 'show'])->name('staff.show');
        Route::put('staff/{admin}/role', [StaffController::class, 'changeRole'])->name('staff.role');
        Route::post('staff/{admin}/suspend', [StaffController::class, 'suspend'])->name('staff.suspend');
        Route::post('staff/{admin}/reactivate', [StaffController::class, 'reactivate'])->name('staff.reactivate');
        Route::post('staff/{admin}/replace', [StaffController::class, 'replace'])->name('staff.replace');
        Route::post('staff/{admin}/resend', [StaffController::class, 'resendInvite'])->name('staff.resend');
        Route::delete('staff/{admin}', [StaffController::class, 'archive'])->name('staff.archive');

        // Activity log + login history (read-only over the immutable audit log).
        Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });

    Route::middleware('permission:manage_products,admin')->group(function (): void {
        Route::post('products/bulk-delete', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
        Route::resource('products', ProductController::class)->except('show');
        Route::resource('coupons', CouponController::class)->except('show');
        // We will manage quantity discounts within the product edit view or a dedicated controller
        Route::resource('products.quantity-discounts', QuantityDiscountController::class)->except(['show', 'index']);

        // Time-bound promotional pricing (scheduled price overlays).
        Route::get('promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('promotions', [PromotionController::class, 'store'])->name('promotions.store');
        Route::delete('promotions/{product}', [PromotionController::class, 'destroy'])->name('promotions.destroy');

        // Bulk price adjustments across a category or the whole catalog.
        Route::get('pricing/bulk', [BulkPricingController::class, 'create'])->name('pricing.bulk.create');
        Route::post('pricing/bulk/preview', [BulkPricingController::class, 'preview'])->name('pricing.bulk.preview');
        Route::post('pricing/bulk', [BulkPricingController::class, 'store'])->name('pricing.bulk.store');

        // Inventory: stock levels, manual adjustments (audited), and bulk import.
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('inventory/{variant}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::get('inventory/import', [InventoryImportController::class, 'create'])->name('inventory.import.create');
        Route::post('inventory/import/preview', [InventoryImportController::class, 'preview'])->name('inventory.import.preview');
        Route::post('inventory/import', [InventoryImportController::class, 'store'])->name('inventory.import.store');

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::post('brands', [BrandController::class, 'store'])->name('brands.store');

        // Review moderation queue.
        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::post('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');

        // Homepage banner CMS.
        Route::get('banners', [BannerController::class, 'index'])->name('banners.index');
        Route::post('banners', [BannerController::class, 'store'])->name('banners.store');
        Route::put('banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
        Route::delete('banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
    });

    Route::middleware('permission:manage_orders,admin')->group(function (): void {
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/export', [OrderController::class, 'export'])->name('orders.export');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');

        // Logistics: dispatch console + pickup-point management.
        Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::post('shipments/{shipment}/advance', [ShipmentController::class, 'advance'])->name('shipments.advance');

        Route::get('logistics', [LogisticsController::class, 'index'])->name('logistics.index');
        Route::resource('locations', LocationController::class)->except(['show', 'create', 'edit']);

        Route::resource('delivery-zones', DeliveryZoneController::class)->except('show');
    });

    Route::middleware('permission:manage_customers,admin')->group(function (): void {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::get('wholesale', [WholesaleController::class, 'index'])->name('wholesale.index');
        Route::post('wholesale/{user}/approve', [WholesaleController::class, 'approve'])->name('wholesale.approve');
        Route::post('wholesale/{user}/reject', [WholesaleController::class, 'reject'])->name('wholesale.reject');

        // Bulk-quantity leads captured from the storefront product page.
        Route::get('bulk-requests', [BulkRequestController::class, 'index'])->name('bulk-requests.index');
        Route::post('bulk-requests/{bulkQuantityRequest}/status', [BulkRequestController::class, 'updateStatus'])->name('bulk-requests.status');
    });

    // Money OUT — refunds + customer wallet funding. Kept separate from payment
    // reconciliation so "can reconcile payments" never implies "can move money out."
    Route::middleware('permission:manage_refunds,admin')->group(function (): void {
        Route::get('store-credits', [StoreCreditController::class, 'index'])->name('store-credits.index');
        Route::post('store-credits', [StoreCreditController::class, 'issue'])->name('store-credits.issue');
        Route::get('store-credits/{user}', [StoreCreditController::class, 'show'])->name('store-credits.show');
        Route::post('orders/{order}/refund', [PaymentController::class, 'refund'])->name('orders.refund');
    });

    Route::middleware('permission:manage_returns,admin')->group(function (): void {
        Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::get('returns/export', [ReturnController::class, 'export'])->name('returns.export');
        Route::get('returns/{return}', [ReturnController::class, 'show'])->name('returns.show');
        Route::post('returns/{return}/approve', [ReturnController::class, 'approve'])->name('returns.approve');
        Route::post('returns/{return}/deny', [ReturnController::class, 'deny'])->name('returns.deny');
        Route::post('returns/{return}/request-info', [ReturnController::class, 'requestInfo'])->name('returns.request-info');
        Route::post('returns/{return}/receive', [ReturnController::class, 'receive'])->name('returns.receive');
        Route::post('returns/{return}/inspect', [ReturnController::class, 'inspect'])->name('returns.inspect');
        Route::post('returns/{return}/settle', [ReturnController::class, 'settle'])->name('returns.settle');

    });

    Route::middleware('permission:manage_payments,admin')->group(function (): void {
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments/{payment}/verify', [PaymentController::class, 'verifyPayment'])->name('payments.verify');
        Route::post('payments/{payment}/mark-paid', [PaymentController::class, 'markPaid'])->name('payments.mark-paid');
        Route::post('payments/{payment}/mark-failed', [PaymentController::class, 'markFailed'])->name('payments.mark-failed');
        Route::post('orders/{order}/pod-collected', [PaymentController::class, 'markPodCollected'])->name('orders.pod-collected');

        // Manual bank transfer reconciliation.
        Route::get('transfers', [TransferReconciliationController::class, 'index'])->name('transfers.index');
        Route::post('transfers/{proof}/approve', [TransferReconciliationController::class, 'approve'])->name('transfers.approve');
        Route::post('transfers/{proof}/reject', [TransferReconciliationController::class, 'reject'])->name('transfers.reject');

        // Daily reconciliation report.
        Route::get('reconciliation', [PaymentController::class, 'reconciliation'])->name('reconciliation');
    });

    Route::middleware('permission:view_analytics,admin')->group(function (): void {
        Route::get('analytics', AnalyticsController::class)->name('analytics');
    });

    // Notifications are available to every authenticated admin.
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');

    // Web Push (PWA) subscription management for the logged-in admin.
    Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});
