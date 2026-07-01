<?php

namespace App\Admin\Controllers\Auth;

use App\Admin\Models\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

/**
 * Public, signed-link flow where an invited staff member sets their password and
 * is signed in. Reached only via the temporary signed URL in the invite email.
 */
class StaffActivationController extends Controller
{
    public function create(Admin $admin): View|RedirectResponse
    {
        if ($this->alreadyActivated($admin)) {
            return redirect()->route('admin.login')->with('status', 'This invitation has already been used — please sign in.');
        }

        return view('admin.auth.activate', [
            'staff' => $admin,
            'action' => URL::signedRoute('admin.staff.activate.store', ['admin' => $admin->id]),
        ]);
    }

    public function store(Request $request, Admin $admin): RedirectResponse
    {
        if ($this->alreadyActivated($admin)) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $admin->forceFill(['password' => $data['password'], 'last_login_at' => now()])->save();

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('status', 'Welcome to gobuy — your account is ready.');
    }

    private function alreadyActivated(Admin $admin): bool
    {
        return $admin->last_login_at !== null;
    }
}
