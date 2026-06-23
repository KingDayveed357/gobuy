<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Returns\Models\StoreCredit;
use App\Modules\Returns\Models\StoreCreditEntry;
use App\Modules\Returns\Services\StoreCreditService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreCreditController extends Controller
{
    public function __construct(private readonly StoreCreditService $storeCredit) {}

    public function index(Request $request): View
    {
        $wallets = StoreCredit::query()
            ->with('user:id,name,email')
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = $request->string('q')->toString();
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderByDesc('balance')
            ->paginate(20)
            ->withQueryString();

        return view('admin.store-credits.index', ['wallets' => $wallets]);
    }

    public function show(User $user): View
    {
        $walletId = StoreCredit::where('user_id', $user->id)->value('id');
        $entries = StoreCreditEntry::where('store_credit_id', $walletId ?? 0)
            ->with('admin:id,name')
            ->latest('id')
            ->paginate(20);

        return view('admin.store-credits.show', [
            'customer' => $user,
            'balance' => $this->storeCredit->balanceFor($user),
            'entries' => $entries,
        ]);
    }

    public function issue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'reason' => ['required', 'string', 'max:160'],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();

        $this->storeCredit->issue(
            $user,
            Money::fromNaira($data['amount']),
            null,
            null,
            $data['reason'],
            Auth::guard('admin')->user(),
        );

        return redirect()->route('admin.store-credits.show', $user)
            ->with('status', money(Money::fromNaira($data['amount'])).' credited to '.$user->name.'.');
    }
}
