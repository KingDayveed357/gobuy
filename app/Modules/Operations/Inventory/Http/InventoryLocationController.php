<?php

namespace App\Modules\Operations\Inventory\Http;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryLocation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Manage inventory locations (shop, home storage, warehouse…) and see stock per
 * location. The ops.inventory_ledger module's UI on top of the CO-1 ledger.
 */
class InventoryLocationController extends Controller
{
    public function index(): View
    {
        $locations = InventoryLocation::query()
            ->withSum('stockLevels as units_on_hand', 'on_hand')
            ->withCount(['stockLevels as sku_count' => fn ($q) => $q->where('on_hand', '>', 0)])
            ->orderByDesc('is_default')->orderBy('name')->get();

        return view('admin.stock-locations.index', ['locations' => $locations]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        InventoryLocation::create($data + ['is_active' => true]);

        return back()->with('status', 'Location added.');
    }

    public function update(Request $request, InventoryLocation $location): RedirectResponse
    {
        $location->update($this->validated($request, $location));

        return back()->with('status', 'Location updated.');
    }

    public function show(InventoryLocation $location): View
    {
        $levels = $location->stockLevels()
            ->where('on_hand', '!=', 0)
            ->with('variant.product:id,name')
            ->get()
            ->sortByDesc('on_hand')
            ->values();

        return view('admin.stock-locations.show', ['location' => $location, 'levels' => $levels]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?InventoryLocation $location = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['nullable', 'string', 'max:32'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('inventory_locations', 'code')->ignore($location?->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = ($data['code'] ?? null) ?: Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }
}
