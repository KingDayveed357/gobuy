<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Admin\Models\Admin;
use App\Admin\Notifications\AdminAlertNotification;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Customer\Http\Requests\RegisterRequest;
use App\Modules\Customer\Services\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class RegisterController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'password']),
            'role' => User::ROLE_CUSTOMER,
            'customer_type' => User::TYPE_RETAIL,
            'wholesale_status' => User::WHOLESALE_NONE,
        ]);

        Notification::send(
            Admin::withAbility('manage_customers'),
            new AdminAlertNotification(
                'New customer registered',
                "{$user->name} ({$user->email}) just created an account.",
                'important',
                route('admin.customers.index'),
                'fa-user-plus',
            ),
        );

        // Login fires the Login event, which merges the guest cart.
        Auth::login($user);
        $request->session()->regenerate();

        // Kick off email verification with a one-time passcode.
        $this->otp->issue($user);

        return redirect()->route('verification.notice')->with('status', 'Welcome to gobuy! Check your email for a verification code.');
    }
}
