<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function dashboard(): View
    {
        $user = Auth::user();

        return view('account.dashboard', [
            'user' => $user,
            'recentOrders' => $user->orders()->with('items')->take(5)->get(),
        ]);
    }

    public function orders(): View
    {
        $orders = Auth::user()->orders()->with('items')->paginate(10);

        return view('account.orders', ['orders' => $orders]);
    }
}
