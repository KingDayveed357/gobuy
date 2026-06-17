<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index', [
            'notifications' => Auth::guard('admin')->user()->notifications()->paginate(20),
        ]);
    }

    public function markAllRead(): RedirectResponse
    {
        Auth::guard('admin')->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Notifications marked as read.');
    }
}
