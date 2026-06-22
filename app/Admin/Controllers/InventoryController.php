<?php

namespace App\Admin\Controllers;

use App\Admin\Http\Requests\AdjustStockRequest;
use App\Http\Controllers\Controller;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockReservation;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString(); // '', 'low', 'out'

        $variants = ProductVariant::query()
            ->with('product:id,name,slug,brand_id')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->toString();
                $query->where(fn ($q) => $q->where('sku', 'like', "%{$term}%")
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%")));
            })
            ->when($filter === 'low', fn ($q) => $q->whereColumn('stock', '<=', 'low_stock_threshold')->where('stock', '>', 0))
            ->when($filter === 'out', fn ($q) => $q->where('stock', '<=', 0))
            ->orderBy('stock')
            ->paginate(25)
            ->withQueryString();

        // Reserved quantities for the variants on this page (single query, no N+1).
        $reserved = StockReservation::query()
            ->active()
            ->whereIn('product_variant_id', $variants->pluck('id'))
            ->selectRaw('product_variant_id, SUM(quantity) as held')
            ->groupBy('product_variant_id')
            ->pluck('held', 'product_variant_id');

        $counts = [
            'all' => ProductVariant::count(),
            'low' => ProductVariant::whereColumn('stock', '<=', 'low_stock_threshold')->where('stock', '>', 0)->count(),
            'out' => ProductVariant::where('stock', '<=', 0)->count(),
        ];

        return view('admin.inventory.index', [
            'variants' => $variants,
            'reserved' => $reserved,
            'counts' => $counts,
            'filter' => $filter,
        ]);
    }

    public function adjust(AdjustStockRequest $request, ProductVariant $variant): RedirectResponse
    {
        $admin = $request->user('admin');
        $amount = (int) $request->integer('amount');
        $reason = $request->string('reason')->toString() ?: null;

        if ($request->input('mode') === 'set') {
            $this->inventory->setStock($variant, $amount, $reason, $admin);
        } else {
            $this->inventory->adjust($variant, $amount, $reason, $admin);
        }

        return back()->with('status', "Stock updated for {$variant->sku}.");
    }
}
