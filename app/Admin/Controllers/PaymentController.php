<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\BankTransferProof;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\RefundService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly RefundService $refunds,
        private readonly PaymentService $payments,
    ) {}

    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with('order')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('reference', 'like', '%'.$request->string('q')->toString().'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', ['payments' => $payments]);
    }

    public function refund(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:1'],
        ]);

        $amount = isset($validated['amount']) ? Money::fromNaira($validated['amount']) : null;

        try {
            $this->refunds->refund($order, Auth::guard('admin')->user(), $amount, $validated['reason'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Refund issued for {$order->order_number}.");
    }

    public function markPodCollected(Order $order): RedirectResponse
    {
        if ($order->payment_method !== PaymentMethod::PayOnDelivery) {
            return back()->with('error', 'This is not a Pay-on-Delivery order.');
        }

        $this->payments->markPodCollected($order);

        return back()->with('status', "Cash collection recorded for {$order->order_number}.");
    }

    public function reconciliation(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date')->toString())
            : Carbon::today();

        $orders = Order::whereDate('placed_at', $date)->get();

        $byMethod = collect(PaymentMethod::cases())->map(fn (PaymentMethod $m) => [
            'label' => $m->label(),
            'count' => $orders->where('payment_method', $m)->count(),
            'total' => Money::fromKobo((int) $orders->where('payment_method', $m)->sum(fn ($o) => $o->total->kobo)),
        ]);

        $paidOrders = $orders->where('payment_status', PaymentStatus::Paid);

        return view('admin.payments.reconciliation', [
            'date' => $date,
            'byMethod' => $byMethod,
            'ordersTotal' => Money::fromKobo((int) $orders->sum(fn ($o) => $o->total->kobo)),
            'collected' => Money::fromKobo((int) $paidOrders->sum(fn ($o) => $o->total->kobo)),
            'outstanding' => Money::fromKobo((int) $orders->where('payment_status', '!=', PaymentStatus::Paid)->sum(fn ($o) => $o->total->kobo)),
            'paystackSettled' => Money::fromKobo((int) Payment::where('status', 'success')->whereDate('paid_at', $date)->get()->sum(fn ($p) => $p->amount->kobo)),
            'bankConfirmed' => Money::fromKobo((int) BankTransferProof::where('status', 'approved')->whereDate('reviewed_at', $date)->get()->sum(fn ($p) => $p->amount->kobo)),
        ]);
    }
}
