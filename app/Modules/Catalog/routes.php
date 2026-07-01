<?php

use App\Modules\Catalog\Http\Controllers\BackInStockController;
use App\Modules\Catalog\Http\Controllers\BulkQuantityRequestController;
use App\Modules\Catalog\Http\Controllers\HomeController;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Catalog\Http\Controllers\SearchController;
use App\Modules\Catalog\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// SEO endpoints (XML sitemap + robots.txt generated from live catalog data).
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('/wishlist', [ProductController::class, 'wishlist'])->name('wishlist.index');
Route::post('/wishlist/items', [ProductController::class, 'wishlistItems'])->name('wishlist.items');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// Demand capture on the product page — both are public (guests may request too).
Route::post('/back-in-stock', [BackInStockController::class, 'store'])->name('back-in-stock.store');
Route::post('/bulk-quantity-requests', [BulkQuantityRequestController::class, 'store'])->name('bulk-requests.store');
