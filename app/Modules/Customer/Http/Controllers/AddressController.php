<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Http\Requests\AddressRequest;
use App\Modules\Customer\Models\Address;
use App\Modules\Customer\Services\AddressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addresses) {}

    public function index(): View
    {
        return view('account.addresses', [
            'addresses' => Auth::user()->addresses,
        ]);
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $this->addresses->create(Auth::user(), $request->validated());

        return redirect()->route('account.addresses.index')->with('status', 'Address saved.');
    }

    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($address);
        $this->addresses->update($address, $request->validated());

        return redirect()->route('account.addresses.index')->with('status', 'Address updated.');
    }

    public function destroy(Address $address): RedirectResponse
    {
        $this->authorizeOwner($address);
        $this->addresses->delete($address);

        return redirect()->route('account.addresses.index')->with('status', 'Address removed.');
    }

    public function setDefault(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeOwner($address);
        $purpose = $request->string('purpose')->toString() === 'billing' ? 'billing' : 'shipping';
        $this->addresses->setDefault($address, $purpose);

        return redirect()->route('account.addresses.index')->with('status', 'Default address updated.');
    }

    /**
     * Clean JSON list of the user's addresses for the checkout picker.
     */
    public function json(): JsonResponse
    {
        $addresses = Auth::user()->addresses->map(fn (Address $a) => [
            'id' => $a->id,
            'label' => $a->label,
            'recipient_name' => $a->recipient_name,
            'phone' => $a->phone,
            'line1' => $a->line1,
            'line2' => $a->line2,
            'city' => $a->city,
            'state' => $a->state,
            'formatted' => $a->formatted(),
            'is_default_shipping' => $a->is_default_shipping,
            'is_default_billing' => $a->is_default_billing,
        ]);

        return response()->json(['addresses' => $addresses]);
    }

    private function authorizeOwner(Address $address): void
    {
        abort_unless($address->user_id === Auth::id(), 403);
    }
}
