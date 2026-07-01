<?php

namespace App\Admin\Controllers\Auth;

use App\Admin\Models\Admin;
use App\Admin\Services\Admin2faService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Second step of the opt-in admin login: the user has proven their password and
 * a code was emailed; here they enter it to complete sign-in. The pending admin
 * id lives in the session, set by LoginController.
 */
class TwoFactorChallengeController extends Controller
{
    private const SESSION_KEY = 'admin-2fa:id';

    public function __construct(private readonly Admin2faService $twoFactor) {}

    public function create(Request $request): View|RedirectResponse
    {
        return $this->pendingAdmin($request)
            ? view('admin.auth.two-factor-challenge')
            : redirect()->route('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = $this->pendingAdmin($request);

        if (! $admin) {
            return redirect()->route('admin.login');
        }

        $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->verify($admin, $request->string('code')->toString())) {
            throw ValidationException::withMessages(['code' => 'That code is invalid or has expired.']);
        }

        Auth::guard('admin')->login($admin);
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($admin = $this->pendingAdmin($request)) {
            $this->twoFactor->sendCode($admin);
        }

        return back()->with('status', 'A fresh code is on its way.');
    }

    private function pendingAdmin(Request $request): ?Admin
    {
        $id = $request->session()->get(self::SESSION_KEY);

        return $id ? Admin::find($id) : null;
    }
}
