<?php

namespace App\Modules\Operations\Purchasing\Http;

use App\Http\Controllers\Controller;
use App\Modules\Operations\Purchasing\Exceptions\PurchasingException;
use App\Modules\Operations\Purchasing\Models\PurchaseOrder;
use App\Modules\Operations\Purchasing\Services\PurchaseOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Purchase orders — list, raise (a Livewire builder), view & receive (Livewire),
 * plus the place/cancel status actions. Part of the ops.purchasing module.
 */
class PurchaseOrderController extends Controller
{
    public function index(): View
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier:id,name', 'location:id,name', 'items'])
            ->withCount('items')
            ->latest()
            ->paginate(20);

        return view('admin.purchase-orders.index', ['orders' => $orders]);
    }

    public function create(): View
    {
        return view('admin.purchase-orders.create');
    }

    public function edit(PurchaseOrder $purchaseOrder): View|RedirectResponse
    {
        if ($purchaseOrder->status !== \App\Modules\Operations\Purchasing\Enums\PurchaseOrderStatus::Draft) {
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only draft purchase orders can be edited.');
        }

        $purchaseOrder->load(['items']);

        return view('admin.purchase-orders.edit', ['order' => $purchaseOrder]);
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'location', 'createdBy', 'items.variant.product']);

        return view('admin.purchase-orders.show', ['order' => $purchaseOrder]);
    }

    public function place(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $service->place($purchaseOrder);
        } catch (PurchasingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Purchase order placed.');
    }

    public function cancel(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $service->cancel($purchaseOrder);
        } catch (PurchasingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Purchase order cancelled.');
    }
}
