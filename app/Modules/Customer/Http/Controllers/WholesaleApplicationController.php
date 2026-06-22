<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Http\Requests\WholesaleApplicationRequest;
use App\Modules\Customer\Services\WholesaleApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WholesaleApplicationController extends Controller
{
    public function __construct(private readonly WholesaleApprovalService $wholesale) {}

    public function create(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->isWholesale()) {
            return redirect()->route('account.dashboard')->with('status', 'Your account is already a wholesale account.');
        }

        return view('account.wholesale', ['user' => $user->load('wholesaleProfile')]);
    }

    public function store(WholesaleApplicationRequest $request): RedirectResponse
    {
        $this->wholesale->apply(
            Auth::user(),
            $request->validated(),
            (array) $request->file('documents', []),
        );

        return redirect()->route('account.dashboard')
            ->with('status', 'Your wholesale application has been submitted for review.');
    }
}
