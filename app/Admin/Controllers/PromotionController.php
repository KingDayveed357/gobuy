<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Product;
use App\Modules\Pricing\Http\Requests\StorePromotionRequest;
use App\Modules\Pricing\Models\PromotionalPrice;
use App\Modules\Pricing\Services\PromotionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PromotionController extends Controller
{
    public function __construct(private readonly PromotionService $promotions) {}

    public function index(): View
    {
        // One row per product that has any promo, with its window + price span.
        $promotions = PromotionalPrice::query()
            ->with('variant.product')
            ->get()
            ->groupBy(fn (PromotionalPrice $p) => $p->variant?->product?->id)
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'product' => $first->variant?->product,
                    'label' => $first->label,
                    'from' => $group->min(fn (PromotionalPrice $p) => $p->price->kobo),
                    'to' => $group->max(fn (PromotionalPrice $p) => $p->price->kobo),
                    'starts_at' => $first->starts_at,
                    'ends_at' => $first->ends_at,
                    'live' => $group->contains(fn (PromotionalPrice $p) => $p->isLive()),
                    'count' => $group->count(),
                ];
            })
            ->filter(fn ($row) => $row['product'] !== null)
            ->sortByDesc(fn ($row) => $row['starts_at'])
            ->values();

        return view('admin.promotions.index', [
            'promotions' => $promotions,
            'products' => Product::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $product = Product::findOrFail($request->integer('product_id'));

        $this->promotions->scheduleForProduct($product, $request->validated());

        return redirect()->route('admin.promotions.index')
            ->with('status', "Promotion scheduled for {$product->name}.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->promotions->endForProduct($product);

        return redirect()->route('admin.promotions.index')
            ->with('status', "Promotion removed from {$product->name}.");
    }
}
