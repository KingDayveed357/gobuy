<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\BackInStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BackInStockController extends Controller
{
    public function store(Request $request, BackInStockService $service): RedirectResponse
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $service->register(
            ProductVariant::findOrFail($data['product_variant_id']),
            $data['email'],
            $request->user()?->id,
        );

        return back()->with('status', "We’ll email {$data['email']} the moment this item is back in stock.");
    }
}
