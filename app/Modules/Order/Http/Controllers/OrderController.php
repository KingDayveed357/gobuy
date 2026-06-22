<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Http\Requests\TrackOrderRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\BankTransferProof;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function success(Order $order): View
    {
        $order->load(['items', 'statusHistories', 'payment', 'shipment.pickupLocation']);

        return view('storefront.orders.success', ['order' => $order]);
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
}
