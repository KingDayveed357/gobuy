<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Logistics\Models\DeliveryZone;
use App\Modules\Logistics\Models\PickupLocation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(): View
    {
        return view('admin.locations.index', [
            'locations' => \App\Modules\Logistics\Models\Location::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        \App\Modules\Logistics\Models\Location::create($this->validated($request));

        return back()->with('status', 'Location added.');
    }

    public function update(Request $request, \App\Modules\Logistics\Models\Location $location): RedirectResponse
    {
        $location->update($this->validated($request));

        return back()->with('status', 'Location updated.');
    }

    public function destroy(\App\Modules\Logistics\Models\Location $location): RedirectResponse
    {
        $location->delete();

        return back()->with('status', 'Location removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'is_pickup' => ['boolean'],
            'is_return' => ['boolean'],
            'is_default_return' => ['boolean'],
            'is_active' => ['boolean'],
        ]);
    }
}
