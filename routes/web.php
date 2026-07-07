<?php

// Web routes are registered per-module by App\Providers\ModuleServiceProvider,
// which loads each module's routes.php and admin.php files automatically.
// Keep this file empty unless a route truly belongs to no module.

use App\Http\Controllers\NewsletterController;

Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.store');
