<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Logistics\Models\Location;
use App\Modules\Logistics\Models\Shipment;
use App\Modules\Order\Actions\PlaceOrderAction;
use App\Modules\Order\DTOs\CheckoutData;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Http\Requests\CheckoutRequest;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\CheckoutCalculator;
use App\Modules\Order\Services\PaymentOptionsService;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Pricing\Services\CouponService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PlaceOrderAction $placeOrder,
        private readonly PaymentService $payments,
        private readonly PaymentOptionsService $paymentOptions,
        private readonly CheckoutCalculator $calculator,
    ) {}

    public function show(): View|RedirectResponse
    {
        $summary = $this->cart->summary();

        if (empty($summary['lines'])) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        $user = auth()->user();
        $addresses = $user ? $user->addresses : collect();
        $defaultAddress = $user?->defaultShippingAddress();

        $applyCredit = (bool) session(CheckoutCalculator::CREDIT_SESSION_KEY);

        $totals = $this->calculator->calculate(
            $user,
            Shipment::METHOD_HOME,
            $defaultAddress?->state ?? '',
            $applyCredit,
            $summary
        );

        $checkoutToken = (string) Str::uuid();

        return view('storefront.checkout.show', [
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'pickupLocations' => Location::pickup()->active()->orderBy('name')->get(),
            'podEligible' => $this->paymentOptions->podEligible($summary['subtotal'], $user),
            'bankAccount' => config('gobuy.bank_account'),
            'checkoutToken' => $checkoutToken,
            ...$totals,
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $token = $data['checkout_token'] ?? null;

        if ($token) {
            $existingOrder = Order::where('checkout_token', $token)->first();
            if ($existingOrder) {
                return redirect()->route('orders.success', $existingOrder);
            }
        }

        $method = $data['payment_method'] ?? PaymentMethod::Paystack->value;

        // For pickup, snapshot the pickup point's address onto the order.
        if (($data['delivery_method'] ?? null) === 'pickup') {
            $location = Location::pickup()->findOrFail($data['pickup_location_id']);
            $data['address_line'] = $location->address;
            $data['city'] = $location->city;
            $data['state'] = $location->state;
        }

        // Re-check POD eligibility server-side (never trust the form).
        if ($method === PaymentMethod::PayOnDelivery->value
            && ! $this->paymentOptions->podEligible($this->cart->summary()['subtotal'], $request->user())) {
            return redirect()->route('checkout.show')
                ->with('error', 'Pay on delivery is not available for this order.');
        }

        try {
            $order = $this->placeOrder->execute(CheckoutData::fromArray($data));

            // Remember this order in the session so the (public) order pages are
            // viewable by whoever placed it — without being enumerable by others.
            session()->push('viewable_orders', $order->id);

            // Store credit covers the whole bill → no gateway/POD needed.
            if ($order->amountDue()->isZero() && $order->store_credit_applied->isPositive()) {
                $this->payments->confirmManualPayment($order); // completes + spends the credit
                $this->clearCheckoutState();
                $note = $order->store_credit_applied->equals($order->total)
                    ? 'Order confirmed! Paid in full with store credit.'
                    : 'Order confirmed! Paid with store credit.';

                return redirect()->route('orders.success', $order)->with('status', $note);
            }

            return match ($method) {
                // Order is placed (awaiting manual reconciliation) — checkout state can be cleared.
                PaymentMethod::BankTransfer->value => tap(
                    redirect()->route('orders.transfer.show', $order)
                        ->with('status', 'Order placed. Please complete your bank transfer.'),
                    fn () => $this->clearCheckoutState(),
                ),
                PaymentMethod::PayOnDelivery->value => tap(
                    redirect()->route('orders.success', $order)->with('status', 'Order confirmed! Pay on delivery.'),
                    function () use ($order): void {
                        $this->payments->placePodOrder($order);
                        $this->clearCheckoutState();
                    },
                ),
                // Paystack: do NOT tear down checkout state here. The order is not yet
                // paid; if the shopper cancels on the gateway and returns, their coupon
                // and store-credit selection must still be intact. The payment callback
                // clears state only once the charge is actually confirmed.
                default => redirect()->away($this->payments->initializeFor($order, route('payment.callback'))),
            };
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('checkout.show')
                ->with('error', 'We could not place your order. Please try again.');
        }
    }

    public function toggleStoreCredit(Request $request): RedirectResponse
    {
        if ($request->boolean('apply')) {
            session([CheckoutCalculator::CREDIT_SESSION_KEY => true]);

            return back()->with('status', 'Store credit applied.');
        }

        session()->forget(CheckoutCalculator::CREDIT_SESSION_KEY);

        return back()->with('status', 'Store credit removed.');
    }

    /**
     * Clear the per-checkout session flags once an order has been committed to a
     * terminal-for-checkout state (paid, accepted, or awaiting bank transfer).
     * Deliberately does NOT clear the cart — existing flows clear the cart in the
     * payment callback — and is never called on the Paystack redirect path, where
     * the shopper may still cancel and return.
     */
    private function clearCheckoutState(): void
    {
        session()->forget([CouponService::SESSION_KEY, CheckoutCalculator::CREDIT_SESSION_KEY]);
    }
}
