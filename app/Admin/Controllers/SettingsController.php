<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Commerce\CommerceModules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    /**
     * Keys the store-settings screen manages.
     *
     * @var array<int, string>
     */
    private const STORE_KEYS = [
        'store_name',
        'store_email',
        'store_phone',
        'whatsapp_number',
        'instagram_url',
        'facebook_url',
        'x_url',
        'free_delivery_note',
    ];

    public function index(): View
    {
        return view('admin.settings.profile', [
            'admin' => Auth::guard('admin')->user(),
        ]);
    }

    public function store(CommerceModules $modules): View
    {
        return view('admin.settings.store', [
            'settings' => Setting::all(),
            'defaults' => ['store_name' => config('app.name', 'GoBuy')],
            'modules' => $modules,
        ]);
    }

    /**
     * Turn optional Commerce Operations modules on/off. Dependencies are pulled
     * on with the module that needs them; nothing else is touched. Super-admin
     * only (this route sits in the super_admin group).
     */
    public function updateModules(Request $request, CommerceModules $modules): RedirectResponse
    {
        $requested = collect($request->input('modules', []))
            ->filter(fn ($key) => is_string($key) && $modules->isShipped($key));

        // A module implies its dependencies — expand the desired set to include them.
        $desired = collect();
        $expand = function (string $key) use (&$expand, $modules, $desired): void {
            if ($desired->contains($key)) {
                return;
            }
            $desired->push($key);
            foreach ($modules->dependencies($key) as $dependency) {
                $expand($dependency);
            }
        };
        $requested->each($expand);

        foreach (array_keys($modules->available()) as $key) {
            $desired->contains($key) ? $modules->enable($key) : $modules->disable($key);
        }

        return back()->with('status', 'Modules updated.');
    }

    public function updateStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['nullable', 'string', 'max:120'],
            'store_email' => ['nullable', 'email', 'max:160'],
            'store_phone' => ['nullable', 'string', 'max:40'],
            'whatsapp_number' => ['nullable', 'string', 'max:40'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'x_url' => ['nullable', 'url', 'max:255'],
            'free_delivery_note' => ['nullable', 'string', 'max:160'],
        ]);

        Setting::putMany(array_intersect_key($validated, array_flip(self::STORE_KEYS)));

        return back()->with('status', 'Store settings saved.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email,'.$admin->id],
        ]);

        $admin->update($validated);

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'Password updated successfully.');
    }

    public function toggleTwoFactor(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        $admin->forceFill(['two_factor_enabled' => ! $admin->two_factor_enabled])->save();

        return back()->with('status', $admin->two_factor_enabled
            ? "Two-factor authentication is on — you'll get an email code each time you sign in."
            : 'Two-factor authentication is off.');
    }
}
