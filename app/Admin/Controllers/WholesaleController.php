<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Customer\Services\WholesaleApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WholesaleController extends Controller
{
    public function __construct(private readonly WholesaleApprovalService $wholesale) {}

    public function index(): View
    {
        $applicants = User::with('wholesaleProfile')
            ->where('wholesale_status', User::WHOLESALE_PENDING)
            ->latest()
            ->paginate(20);

        return view('admin.wholesale.index', ['applicants' => $applicants]);
    }

    public function approve(User $user): RedirectResponse
    {
        $this->wholesale->approve($user, Auth::guard('admin')->user());

        return back()->with('status', "{$user->name} approved for wholesale.");
    }

    public function reject(User $user): RedirectResponse
    {
        $this->wholesale->reject($user, Auth::guard('admin')->user());

        return back()->with('status', "{$user->name}'s application was rejected.");
    }
}
