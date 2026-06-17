<?php

namespace App\View\Components;

use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Services\PriceResolver;
use App\Modules\Pricing\ValueObjects\ResolvedPrice;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PriceTag extends Component
{
    public ResolvedPrice $price;

    public function __construct(Product $product, int $quantity = 1)
    {
        $this->price = app(PriceResolver::class)->for($product, auth()->user(), $quantity);
    }

    public function render(): View
    {
        return view('components.price-tag');
    }
}
