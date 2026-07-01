<?php

namespace App\Admin\Controllers\Auth;

use App\Admin\Http\Requests\AdminLoginRequest;
use App\Admin\Services\Admin2faService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request, Admin2faService $twoFactor): RedirectResponse
    {
        $request->authenticate();
        $admin = Auth::guard('admin')->user();

        // Opt-in 2FA: park the verified credentials, sign out, and email a code.
        if ($admin->two_factor_enabled) {
            Auth::guard('admin')->logout();
            $request->session()->put('admin-2fa:id', $admin->getKey());
            $twoFactor->sendCode($admin);

            return redirect()->route('admin.2fa.challenge');
        }

        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
