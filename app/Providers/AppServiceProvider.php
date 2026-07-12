<?php

namespace App\Providers;

use App\Admin\Models\Admin;
use App\Modules\Cart\Listeners\MergeGuestCart;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\SearchTerm;
use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Inventory\Listeners\DeductInventoryForOrder;
use App\Modules\Inventory\Listeners\ReleaseInventoryForOrder;
use App\Modules\Inventory\Listeners\ReserveInventoryForOrder;
use App\Modules\Logistics\Listeners\SyncShipmentToOrderStatus;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Models\ProductCollection;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderPaid;
use App\Modules\Order\Events\OrderPlaced;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Listeners\NotifyCustomerOfCancellation;
use App\Modules\Order\Listeners\NotifyCustomerOfCompletion;
use App\Modules\Order\Listeners\SendOrderAcceptedNotifications;
use App\Modules\Order\Models\Order;
use App\Support\Commerce\CommerceModules;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The single authority on optional Commerce Operations modules.
        $this->app->singleton(CommerceModules::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // The platform owner (Super Admin) is unrestricted by design. Returning
        // null (not false) for everyone else lets normal Spatie/policy checks run.
        Gate::before(fn ($user, string $ability) => $user instanceof Admin && $user->isSuperAdmin() ? true : null);

        Event::listen(Login::class, MergeGuestCart::class);
        Event::listen(OrderPlaced::class, ReserveInventoryForOrder::class);
        Event::listen(OrderPaid::class, DeductInventoryForOrder::class);
        Event::listen(OrderPaid::class, SendOrderAcceptedNotifications::class);
        Event::listen(OrderCancelled::class, ReleaseInventoryForOrder::class);
        Event::listen(OrderCancelled::class, NotifyCustomerOfCancellation::class);
        Event::listen(OrderStatusChanged::class, SyncShipmentToOrderStatus::class);
        Event::listen(OrderStatusChanged::class, NotifyCustomerOfCompletion::class);

        // Invalidate the cached page whenever its inputs change. A section clears
        // its own page; banners/collections can appear on any page, so clear home
        // (the only cached page — landing pages resolve fresh).
        HomepageSection::saved(fn (HomepageSection $s) => HomepageMerchandiser::forget($s->placement ?? 'home'));
        HomepageSection::deleted(fn (HomepageSection $s) => HomepageMerchandiser::forget($s->placement ?? 'home'));
        $forgetHome = fn () => HomepageMerchandiser::forget();
        ProductCollection::saved($forgetHome);
        ProductCollection::deleted($forgetHome);
        Banner::saved($forgetHome);
        Banner::deleted($forgetHome);

        Order::updated(function (Order $order) {
            if ($order->wasChanged('status') && $order->status === OrderStatus::Cancelled) {
                OrderCancelled::dispatch($order);
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
