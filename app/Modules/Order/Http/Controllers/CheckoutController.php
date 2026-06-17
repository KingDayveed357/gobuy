<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Order\Actions\PlaceOrderAction;
use App\Modules\Order\DTOs\CheckoutData;
use App\Modules\Order\Http\Requests\CheckoutRequest;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PlaceOrderAction $placeOrder,
        private readonly PaymentService $payments,
    ) {}

    public function show(): View|RedirectResponse
    {
        $summary = $this->cart->summary();

        if (empty($summary['lines'])) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        return view('storefront.checkout.show', [
            ...$summary,
            'deliveryFee' => (float) config('gobuy.delivery_fee'),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        try {
            $order = $this->placeOrder->execute(CheckoutData::fromArray($request->validated()));

            $authorizationUrl = $this->payments->initializeFor(
                $order,
                route('payment.callback'),
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('checkout.show')
                ->with('error', 'We could not start your payment. Please try again.');
        }

        return redirect()->away($authorizationUrl);
    }
}
