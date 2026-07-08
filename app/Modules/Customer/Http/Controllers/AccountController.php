<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\ReorderService;
use App\Modules\Returns\Models\StoreCredit;
use App\Modules\Returns\Models\StoreCreditEntry;
use App\Modules\Returns\Services\StoreCreditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly StoreCreditService $storeCredit,
        private readonly ReorderService $reorder,
    ) {}

    public function reorderPreview(Order $order): View|RedirectResponse
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $preview = $this->reorder->preview($order, Auth::user());

        if ($preview['addable'] === 0) {
            return redirect()->route('account.orders')
                ->with('error', 'None of the items from that order are available to reorder right now.');
        }

        return view('account.reorder-preview', ['order' => $order, 'preview' => $preview]);
    }

    public function wallet(): View
    {
        $user = Auth::user();
        $walletId = StoreCredit::where('user_id', $user->id)->value('id');

        $entries = StoreCreditEntry::where('store_credit_id', $walletId ?? 0)
            ->latest('id')
            ->paginate(15);

        return view('account.wallet', [
            'balance' => $this->storeCredit->balanceFor($user),
            'entries' => $entries,
        ]);
    }

    public function dashboard(): View
    {
        $user = Auth::user();

        return view('account.dashboard', [
            'user' => $user,
            'balance' => $this->storeCredit->balanceFor($user),
            'recentOrders' => $user->orders()->latest()->with('items')->take(5)->get(),
        ]);
    }

    public function orders(Request $request): View
    {
        $query = Auth::user()->orders()->with('items')->latest();

        if ($search = $request->input('search')) {
            $query->where('order_number', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($sort = $request->input('sort')) {
            switch ($sort) {
                case 'oldest':
                    $query->reorder('created_at', 'asc');
                    break;
                case 'highest_value':
                    $query->reorder('total', 'desc');
                    break;
                case 'lowest_value':
                    $query->reorder('total', 'asc');
                    break;
            }
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('account.orders', ['orders' => $orders]);
    }

    /**
     * Re-add a past order's still-available items to the cart (capped at the
     * stock on hand), then send the shopper to their cart.
     */
    public function reorder(Order $order): RedirectResponse
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $added = 0;
        $skipped = 0;

        foreach ($order->items as $item) {
            $variant = ProductVariant::with('product')->find($item->product_variant_id);

            if (! $variant || $variant->product?->status !== 'active' || $variant->stock < 1) {
                $skipped++;

                continue;
            }

            $this->cart->add($variant, min($item->quantity, $variant->stock));
            $added++;
        }

        if ($added === 0) {
            return redirect()->route('account.orders')
                ->with('error', 'None of the items from that order are available to reorder right now.');
        }

        $message = "{$added} item(s) added to your cart.".($skipped > 0 ? " {$skipped} item(s) were unavailable and skipped." : '');

        return redirect()->route('cart.index')->with('status', $message);
    }

    public function settings(): View
    {
        return view('account.settings', [
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->update($validated);

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'Password updated successfully.');
    }
}
