<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Customer\Http\Requests\RegisterRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
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

        // Login fires the Login event, which merges the guest cart.
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard')->with('status', 'Welcome to gobuy!');
    }
}
