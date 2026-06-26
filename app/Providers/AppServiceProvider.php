<?php

namespace App\Providers;

use App\Modules\Cart\Listeners\MergeGuestCart;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\SearchTerm;
use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Inventory\Listeners\DeductInventoryForOrder;
use App\Modules\Inventory\Listeners\ReleaseInventoryForOrder;
use App\Modules\Inventory\Listeners\ReserveInventoryForOrder;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderPaid;
use App\Modules\Order\Events\OrderPlaced;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Event::listen(Login::class, MergeGuestCart::class);
        Event::listen(OrderPlaced::class, ReserveInventoryForOrder::class);
        Event::listen(OrderPaid::class, DeductInventoryForOrder::class);
        Event::listen(OrderPaid::class, \App\Modules\Order\Listeners\SendOrderAcceptedNotifications::class);
        Event::listen(OrderCancelled::class, ReleaseInventoryForOrder::class);

        \App\Modules\Order\Models\Order::updated(function (\App\Modules\Order\Models\Order $order) {
            if ($order->wasChanged('status') && $order->status === \App\Modules\Order\Enums\OrderStatus::Cancelled) {
                \App\Modules\Order\Events\OrderCancelled::dispatch($order);
            }
        });

        View::composer(['partials.storefront-nav', 'partials.footer'], function ($view): void {
            $view->with('cartCount', app(CartService::class)->count());

            // Wishlist state for the navbar badge + initial heart states.
            // Always read the customer (web) guard — never the admin guard.
            $customer = Auth::guard('web')->user();
            $wishlistIds = $customer ? $customer->wishlistItems()->pluck('product_id') : collect();
            $view->with('wishlistIds', $wishlistIds);
            $view->with('wishlistCount', $wishlistIds->count());

            // Trending tolerates staleness — cache it off the hot path (runs on every page).
            $view->with('trendingSearches', Cache::remember('trending_searches', 600, fn () => SearchTerm::trending()));

            $view->with('navCategories', Cache::remember(CategoryService::NAV_CACHE_KEY, 3600, function () {
                return Category::active()
                    ->roots()
                    ->with(['children' => function ($query) {
                        $query->active()->orderBy('sort_order')->orderBy('name')->with(['children' => function ($query) {
                            $query->active()->orderBy('sort_order')->orderBy('name');
                        }]);
                    }])
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
            }));
        });
    }
}
