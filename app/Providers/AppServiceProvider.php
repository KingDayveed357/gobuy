<?php

namespace App\Providers;

use App\Modules\Cart\Listeners\MergeGuestCart;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\Category;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
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

        View::composer(['partials.storefront-nav', 'partials.footer'], function ($view): void {
            $view->with('cartCount', app(CartService::class)->count());
            $view->with('navCategories', Category::active()->orderBy('sort_order')->get());
        });
    }
}
