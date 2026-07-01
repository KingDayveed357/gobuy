<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Http\Requests\TrackOrderRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStatusService;
use App\Modules\Payment\Models\BankTransferProof;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly OrderStatusService $status,
    ) {}

    public function success(Order $order): View
    {
        $this->authorizeAccess($order);
        $order->load(['items', 'statusHistories', 'payment', 'shipment.pickupLocation']);

        return view('storefront.orders.success', ['order' => $order]);
    }

    /**
     * Re-open Paystack for an order whose payment was abandoned or failed, so the
     * customer can finish paying without rebuilding their cart.
     */
    public function retryPayment(Order $order): RedirectResponse
    {
        $this->authorizeAccess($order);

        if ($order->isPaid() || $order->status !== OrderStatus::Pending || $order->payment_method !== PaymentMethod::Paystack) {
            return redirect()->route('orders.success', $order)->with('error', 'This order can no longer be paid online.');
        }

        try {
            return redirect()->away($this->payments->initializeFor($order, route('payment.callback')));
        } catch (RuntimeException $e) {
            return redirect()->route('orders.success', $order)->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('orders.success', $order)->with('error', 'We could not start your payment. Please try again.');
        }
    }

    /**
     * Let a customer cancel their own order while it is still unpaid and pending.
     */
    public function cancel(Order $order): RedirectResponse
    {
        $this->authorizeAccess($order);

        if ($order->status !== OrderStatus::Pending || $order->payment_status === PaymentStatus::Paid) {
            return redirect()->route('orders.success', $order)->with('error', 'This order can no longer be cancelled.');
        }

        $this->status->transitionTo($order, OrderStatus::Cancelled, 'Cancelled by customer');

        return redirect()->route('orders.success', $order)->with('status', 'Your order has been cancelled.');
    }

    public function trackForm(): View
    {
        return view('storefront.orders.track');
    }

    public function track(TrackOrderRequest $request): View|RedirectResponse
    {
        $order = Order::with(['items', 'statusHistories', 'shipment.pickupLocation'])
            ->where('order_number', $request->validated('order_number'))
            ->where('customer_email', $request->validated('email'))
            ->first();

        if (! $order) {
            return back()
                ->withInput()
                ->with('error', 'No order found for that order number and email.');
        }

        return view('storefront.orders.tracking', ['order' => $order]);
    }

    public function transferShow(Order $order): View|RedirectResponse
    {
        $this->authorizeAccess($order);

        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            return redirect()->route('orders.success', $order);
        }

        return view('storefront.orders.transfer', [
            'order' => $order->load('transferProofs.media'),
            'bankAccount' => config('gobuy.bank_account'),
        ]);
    }

    public function transferStore(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeAccess($order);
        abort_if($order->payment_method !== PaymentMethod::BankTransfer, 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'bank_reference' => ['nullable', 'string', 'max:255'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $proof = $order->transferProofs()->create([
            'amount' => Money::fromNaira($validated['amount']),
            'sender_name' => $validated['sender_name'] ?? null,
            'bank_reference' => $validated['bank_reference'] ?? null,
            'status' => BankTransferProof::STATUS_PENDING,
        ]);

        if ($request->hasFile('receipt')) {
            $proof->addMediaFromRequest('receipt')->toMediaCollection(BankTransferProof::MEDIA_RECEIPT);
        }

        return redirect()->route('orders.transfer.show', $order)
            ->with('status', 'Thanks! We received your proof of payment and will confirm shortly.');
    }

    /**
     * Order pages are public (guest checkout), so they must not be enumerable.
     * Access is granted to the authenticated owner or to whoever placed the order
     * in this browser session (recorded by CheckoutController). Guests who lose
     * their session use the email-gated /track flow instead.
     */
    private function authorizeAccess(Order $order): void
    {
        $owns = auth()->check() && auth()->id() === $order->user_id;
        $placedHere = in_array($order->id, session('viewable_orders', []), true);

        abort_unless($owns || $placedHere, 403);
    }
}
