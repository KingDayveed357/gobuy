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
        private readonly DeliveryFeeService $deliveryFees,
        private readonly PaymentOptionsService $paymentOptions,
        private readonly CouponService $coupons,
        private readonly StoreCreditService $storeCredit,
    ) {}

    private const CREDIT_SESSION_KEY = 'checkout.apply_credit';

    /**
     * Toggle whether the customer's store credit is applied to this order.
     */
    public function toggleStoreCredit(Request $request): RedirectResponse
    {
        session([self::CREDIT_SESSION_KEY => $request->boolean('apply')]);

        return redirect()->route('checkout.show');
    }

    public function show(): View|RedirectResponse
    {
        $summary = $this->cart->summary();

        if (empty($summary['lines'])) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        $user = auth()->user();
        $addresses = $user ? $user->addresses : collect();
        $defaultAddress = $user?->defaultShippingAddress();

        // Initial quote: home delivery to the default address's state.
        $weight = (int) ($summary['weight'] ?? 0);
        $initial = $this->deliveryFees->quote(
            Shipment::METHOD_HOME,
            $defaultAddress?->state ?? '',
            $weight,
            $summary['subtotal'],
        );

        $coupon = $this->coupons->resolveForCart($summary, $user);
        $discount = $coupon['discount'] ?? Money::zero();
        $discountedSubtotal = $summary['subtotal']->minus($discount);
        $total = $discountedSubtotal->plus($initial['fee']);

        // Store credit (a tender against the bill, not a discount).
        $creditAvailable = $user ? $this->storeCredit->balanceFor($user) : Money::zero();
        $applyCredit = (bool) session(self::CREDIT_SESSION_KEY) && $creditAvailable->isPositive();
        $creditApplied = $applyCredit && $user ? $this->storeCredit->redeemableFor($user, $total) : Money::zero();

        return view('storefront.checkout.show', [
            ...$summary,
            'deliveryFee' => $initial['fee'],
            'appliedCoupon' => $coupon['coupon'] ?? null,
            'discount' => $discount,
            'total' => $total,
            'creditAvailable' => $creditAvailable,
            'applyCredit' => $applyCredit,
            'creditApplied' => $creditApplied,
            'amountDue' => $total->minus($creditApplied),
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'pickupLocations' => PickupLocation::active()->orderBy('name')->get(),
            'podEligible' => $this->paymentOptions->podEligible($summary['subtotal'], $user),
            'bankAccount' => config('gobuy.bank_account'),
        ]);
    }

    /**
     * Live delivery fee for the chosen method + destination (never trusted as
     * authoritative — the order recomputes server-side at placement).
     */
    public function deliveryQuote(Request $request): JsonResponse
    {
        $request->validate([
            'delivery_method' => ['required', 'in:home_delivery,pickup'],
            'state' => ['nullable', 'string', 'max:120'],
        ]);

        $summary = $this->cart->summary();
        $quote = $this->deliveryFees->quote(
            $request->string('delivery_method')->toString(),
            $request->string('state')->toString(),
            (int) ($summary['weight'] ?? 0),
            $summary['subtotal'],
        );

        $coupon = $this->coupons->resolveForCart($summary, $request->user());
        $discount = $coupon['discount'] ?? Money::zero();
        $total = $summary['subtotal']->minus($discount)->plus($quote['fee']);

        return response()->json([
            'fee_kobo' => $quote['fee']->kobo,
            'fee_formatted' => money($quote['fee']),
            'total_formatted' => money($total),
            'zone' => $quote['zone']?->name,
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $method = $data['payment_method'] ?? PaymentMethod::Paystack->value;

        // For pickup, snapshot the pickup point's address onto the order.
        if (($data['delivery_method'] ?? null) === Shipment::METHOD_PICKUP) {
            $location = PickupLocation::findOrFail($data['pickup_location_id']);
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
}
