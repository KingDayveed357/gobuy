<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Models\DeliveryZone;
use App\Modules\Logistics\Models\PickupLocation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PickupLocationController extends Controller
{
    public function index(): View
    {
        return view('admin.logistics.index', [
            'zones' => DeliveryZone::with('states')->orderBy('sort_order')->get(),
            'pickups' => PickupLocation::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PickupLocation::create($this->validated($request));

        return back()->with('status', 'Pickup location added.');
    }

    public function update(Request $request, PickupLocation $pickup): RedirectResponse
    {
        $pickup->update($this->validated($request));

        return back()->with('status', 'Pickup location updated.');
    }

    public function destroy(PickupLocation $pickup): RedirectResponse
    {
        $pickup->delete();

        return back()->with('status', 'Pickup location removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
        ]);
    }
}
