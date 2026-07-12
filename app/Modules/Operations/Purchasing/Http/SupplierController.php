<?php

namespace App\Modules\Operations\Purchasing\Http;

use App\Http\Controllers\Controller;
use App\Modules\Operations\Purchasing\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manage suppliers — the businesses a purchase order is raised against. Part of
 * the ops.purchasing module.
 */
class SupplierController extends Controller
{
    public function index(): View
    {
        $suppliers = Supplier::query()
            ->withCount('purchaseOrders')
            ->orderBy('name')
            ->get();

        return view('admin.suppliers.index', ['suppliers' => $suppliers]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validated($request) + ['is_active' => true]);

        return back()->with('status', 'Supplier added.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return back()->with('status', 'Supplier updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }
}
