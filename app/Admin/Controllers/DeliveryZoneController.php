<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Models\DeliveryZone;
use App\Modules\Logistics\Models\DeliveryZoneState;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeliveryZoneController extends Controller
{
    public function index()
    {
        $zones = DeliveryZone::with('states')->orderBy('sort_order')->get();
        return view('admin.delivery_zones.index', compact('zones'));
    }

    public function create()
    {
        return view('admin.delivery_zones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_fee' => ['required', 'numeric', 'min:0'],
            'per_kg_fee' => ['required', 'numeric', 'min:0'],
            'free_over_subtotal' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'states' => ['nullable', 'string'],
        ]);

        $zone = DeliveryZone::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'base_fee' => Money::fromNaira($data['base_fee']),
            'per_kg_fee' => Money::fromNaira($data['per_kg_fee']),
            'free_over_subtotal' => $data['free_over_subtotal'] ? Money::fromNaira($data['free_over_subtotal']) : null,
            'is_active' => $data['is_active'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->syncStates($zone, $data['states'] ?? '');

        return redirect()->route('admin.delivery-zones.index')->with('status', 'Delivery zone created.');
    }

    public function edit(DeliveryZone $deliveryZone)
    {
        $deliveryZone->load('states');
        return view('admin.delivery_zones.edit', compact('deliveryZone'));
    }

    public function update(Request $request, DeliveryZone $deliveryZone)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_fee' => ['required', 'numeric', 'min:0'],
            'per_kg_fee' => ['required', 'numeric', 'min:0'],
            'free_over_subtotal' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'states' => ['nullable', 'string'],
        ]);

        $deliveryZone->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'base_fee' => Money::fromNaira($data['base_fee']),
            'per_kg_fee' => Money::fromNaira($data['per_kg_fee']),
            'free_over_subtotal' => $data['free_over_subtotal'] ? Money::fromNaira($data['free_over_subtotal']) : null,
            'is_active' => $data['is_active'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->syncStates($deliveryZone, $data['states'] ?? '');

        return redirect()->route('admin.delivery-zones.index')->with('status', 'Delivery zone updated.');
    }

    public function destroy(DeliveryZone $deliveryZone)
    {
        $deliveryZone->delete();
        return redirect()->route('admin.delivery-zones.index')->with('status', 'Delivery zone deleted.');
    }

    private function syncStates(DeliveryZone $zone, string $statesString): void
    {
        // Delete all currently mapped to this zone
        $zone->states()->delete();

        // Split by comma, trim, filter empty
        $states = collect(explode(',', $statesString))
            ->map(fn($s) => trim($s))
            ->filter()
            ->unique();

        foreach ($states as $state) {
            // Remove state from any other zone it might be in to avoid unique constraint violations
            DeliveryZoneState::whereRaw('LOWER(state) = ?', [mb_strtolower($state)])->delete();

            $zone->states()->create([
                'state' => $state,
            ]);
        }
    }
}
