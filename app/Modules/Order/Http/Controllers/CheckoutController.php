<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Logistics\Models\PickupLocation;
use App\Modules\Logistics\Models\Shipment;
use App\Modules\Logistics\Services\DeliveryFeeService;
use App\Modules\Order\Actions\PlaceOrderAction;
use App\Modules\Order\DTOs\CheckoutData;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Http\Requests\CheckoutRequest;
use App\Modules\Order\Services\PaymentOptionsService;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Pricing\Services\CouponService;
use App\Modules\Returns\Services\StoreCreditService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PlaceOrderAction $placeOrder,
        private readonly PaymentService $payments,
        private readonly PaymentOptionsService $paymentOptions,
        private readonly \App\Modules\Order\Services\CheckoutCalculator $calculator,
    ) {}

    private const CREDIT_SESSION_KEY = 'checkout.apply_credit';

    public function show(): View|RedirectResponse
    {
        $summary = $this->cart->summary();

        if (empty($summary['lines'])) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        $user = auth()->user();
        $addresses = $user ? $user->addresses : collect();
        $defaultAddress = $user?->defaultShippingAddress();

        $applyCredit = (bool) session(self::CREDIT_SESSION_KEY);

        $totals = $this->calculator->calculate(
            $user,
            \App\Modules\Logistics\Models\Shipment::METHOD_HOME,
            $defaultAddress?->state ?? '',
            $applyCredit,
            $summary
        );

        $checkoutToken = (string) \Illuminate\Support\Str::uuid();

        return view('storefront.checkout.show', [
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'pickupLocations' => \App\Modules\Logistics\Models\Location::pickup()->active()->orderBy('name')->get(),
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
            $existingOrder = \App\Modules\Order\Models\Order::where('checkout_token', $token)->first();
            if ($existingOrder) {
                return redirect()->route('orders.success', $existingOrder);
            }
        }

        $method = $data['payment_method'] ?? PaymentMethod::Paystack->value;

        // For pickup, snapshot the pickup point's address onto the order.
        if (($data['delivery_method'] ?? null) === 'pickup') {
            $location = \App\Modules\Logistics\Models\Location::pickup()->findOrFail($data['pickup_location_id']);
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

            session()->forget(self::CREDIT_SESSION_KEY);

            // Store credit covers the whole bill → no gateway/POD needed.
            if ($order->amountDue()->isZero() && $order->store_credit_applied->isPositive()) {
                $this->payments->confirmManualPayment($order); // completes + spends the credit
                $note = $order->store_credit_applied->equals($order->total)
                    ? 'Order confirmed! Paid in full with store credit.'
                    : 'Order confirmed! Paid with store credit.';

                return redirect()->route('orders.success', $order)->with('status', $note);
            }

            return match ($method) {
                PaymentMethod::BankTransfer->value => redirect()->route('orders.transfer.show', $order)
                    ->with('status', 'Order placed. Please complete your bank transfer.'),
                PaymentMethod::PayOnDelivery->value => tap(
                    redirect()->route('orders.success', $order)->with('status', 'Order confirmed! Pay on delivery.'),
                    fn () => $this->payments->placePodOrder($order),
                ),
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
            session([self::CREDIT_SESSION_KEY => true]);
            return back()->with('status', 'Store credit applied.');
        }

        session()->forget(self::CREDIT_SESSION_KEY);
        return back()->with('status', 'Store credit removed.');
    }
}
