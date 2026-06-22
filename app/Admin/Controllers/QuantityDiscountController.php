<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Http\Requests\StoreQuantityDiscountRequest;
use App\Modules\Pricing\Http\Requests\UpdateQuantityDiscountRequest;
use App\Modules\Pricing\Models\QuantityDiscount;
use Illuminate\Http\RedirectResponse;

class QuantityDiscountController extends Controller
{
    public function store(StoreQuantityDiscountRequest $request, Product $product): RedirectResponse
    {
        $product->quantityDiscounts()->create($request->validated());

        return back()->with('status', 'Quantity discount added.');
    }

    public function update(UpdateQuantityDiscountRequest $request, Product $product, QuantityDiscount $quantityDiscount): RedirectResponse
    {
        $quantityDiscount->update($request->validated());

        return back()->with('status', 'Quantity discount updated.');
    }

    public function destroy(Product $product, QuantityDiscount $quantityDiscount): RedirectResponse
    {
        $quantityDiscount->delete();

        return back()->with('status', 'Quantity discount removed.');
    }
}
