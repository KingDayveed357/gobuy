<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Enums\OrderStatus;
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

        $stats = [
            'pending_count' => Payment::where('status', 'pending')->count(),
            'pending_value' => Money::fromKobo((int) Payment::where('status', 'pending')->sum('amount')),
            'success_count' => Payment::where('status', 'success')->count(),
            'failed_count' => Payment::where('status', 'failed')->count(),
        ];

        return view('admin.payments.index', ['payments' => $payments, 'stats' => $stats]);
    }

    /**
     * Admin override: manually mark a pending payment as paid (and complete the
     * order). For reconciled funds where the gateway never confirmed.
     */
    public function markPaid(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be updated.');
        }

        if (! $payment->order) {
            return back()->with('error', 'This payment has no associated order to complete.');
        }

        if ($payment->order->isPaid()) {
            return back()->with('error', "Order {$payment->order->order_number} is already paid via another reference — this attempt cannot be collected again.");
        }

        $this->payments->markPaidManually($payment, Auth::guard('admin')->user());

        return back()->with('status', "Payment {$payment->reference} marked as paid; order {$payment->order->order_number} completed.");
    }

    /**
     * Admin override: mark a stuck pending payment as failed AND cancel its order
     * so the two stay consistent (no "payment failed but order still pending").
     */
    public function markFailed(Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be updated.');
        }

        $this->payments->failAndCancelOrder($payment, Auth::guard('admin')->user());

        $order = $payment->order?->fresh();
        $note = match (true) {
            $order && $order->status === OrderStatus::Cancelled => "Payment {$payment->reference} marked as failed and order {$order->order_number} cancelled.",
            $order && $order->isPaid() => "Attempt {$payment->reference} marked as failed. Order {$order->order_number} remains paid via another reference.",
            default => "Payment {$payment->reference} marked as failed.",
        };

        return back()->with('status', $note);
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

    public function verifyPayment(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be verified.');
        }

        if ($payment->order && $payment->order->isPaid()) {
            return back()->with('error', "Order {$payment->order->order_number} is already paid via another reference; this attempt is not the successful one.");
        }

        try {
            $success = $this->payments->verifyAndComplete($payment->reference);
            if ($success) {
                return back()->with('status', 'Payment verified successfully.');
            }

            return back()->with('error', 'Payment verification failed or payment is not successful on Paystack.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Error verifying payment: '.$e->getMessage());
        }
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

        // Money that fell through — cancelled orders or failed payments. We will
        // never collect these, so they must NOT count as outstanding.
        $fellThrough = $orders->filter(fn (Order $o) => $o->payment_status !== PaymentStatus::Paid
            && ($o->status === OrderStatus::Cancelled || $o->payment_status === PaymentStatus::Failed));

        // Outstanding = still-open orders we genuinely expect to collect.
        $outstandingOrders = $orders->filter(fn (Order $o) => $o->payment_status !== PaymentStatus::Paid
            && $o->status !== OrderStatus::Cancelled
            && $o->status !== OrderStatus::Refunded
            && $o->payment_status !== PaymentStatus::Failed);

        return view('admin.payments.reconciliation', [
            'date' => $date,
            'byMethod' => $byMethod,
            'ordersTotal' => Money::fromKobo((int) $orders->sum(fn ($o) => $o->total->kobo)),
            'collected' => Money::fromKobo((int) $paidOrders->sum(fn ($o) => $o->total->kobo)),
            'outstanding' => Money::fromKobo((int) $outstandingOrders->sum(fn ($o) => $o->total->kobo)),
            'fellThrough' => Money::fromKobo((int) $fellThrough->sum(fn ($o) => $o->total->kobo)),
            'fellThroughCount' => $fellThrough->count(),
            'paystackSettled' => Money::fromKobo((int) Payment::where('status', 'success')->whereDate('paid_at', $date)->get()->sum(fn ($p) => $p->amount->kobo)),
            'bankConfirmed' => Money::fromKobo((int) BankTransferProof::where('status', 'approved')->whereDate('reviewed_at', $date)->get()->sum(fn ($p) => $p->amount->kobo)),
        ]);
    }
}
