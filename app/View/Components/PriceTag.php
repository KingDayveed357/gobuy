<?php

namespace App\View\Components;

use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Services\PricingEngine;
use App\Modules\Pricing\ValueObjects\ResolvedPrice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class PriceTag extends Component
{
    public ResolvedPrice $price;

    public function __construct(Product $product, int $quantity = 1)
    {
        // Storefront pricing always reads the customer (web) guard, never admin.
        $this->price = app(PricingEngine::class)->priceForProduct($product, Auth::guard('web')->user(), $quantity);
    }

    public function render(): View
    {
        return view('components.price-tag');
    }
}
