<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\Category;
use App\Modules\Pricing\Http\Requests\BulkPriceRequest;
use App\Modules\Pricing\Services\BulkPricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BulkPricingController extends Controller
{
    public function __construct(private readonly BulkPricingService $bulk) {}

    public function create(): View
    {
        return view('admin.pricing.bulk', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'preview' => null,
        ]);
    }

    public function preview(BulkPriceRequest $request): View
    {
        $data = $request->validated();

        return view('admin.pricing.bulk', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'preview' => $this->bulk->preview($data),
            'input' => $data,
        ]);
    }

    public function store(BulkPriceRequest $request): RedirectResponse
    {
        $count = $this->bulk->apply($request->validated());

        return redirect()->route('admin.pricing.bulk.create')
            ->with('status', $count === 0
                ? 'No prices changed — nothing matched or values were unchanged.'
                : "Updated prices on {$count} variant(s). Changes are recorded in price history.");
    }
}
