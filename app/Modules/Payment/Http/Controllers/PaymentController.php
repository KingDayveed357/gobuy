<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly CartService $cart,
    ) {}

    /**
     * Browser redirect target after the customer pays on Paystack.
     */
    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->string('reference')->toString();
        $payment = $reference ? Payment::with('order')->firstWhere('reference', $reference) : null;

        if (! $payment || ! $payment->order) {
            return redirect()->route('cart.index')->with('error', 'Payment reference not found.');
        }

        $paid = $this->payments->verifyAndComplete($reference);

        if ($paid) {
            $this->cart->clear();

            return redirect()->route('orders.success', $payment->order)
                ->with('status', 'Payment successful. Thank you for your order!');
        }

        return redirect()->route('orders.success', $payment->order)
            ->with('error', 'Payment was not completed. You can retry from your order.');
    }

    /**
     * Server-to-server webhook. Verifies the signature, then processes
     * idempotently (verifyAndComplete early-returns if already paid).
     */
    public function webhook(Request $request): Response
    {
        $secret = (string) config('services.paystack.secret_key');
        $signature = $request->header('x-paystack-signature', '');
        $expected = hash_hmac('sha512', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Paystack webhook signature mismatch');

            return response('Invalid signature', 401);
        }

        $event = $request->json();

        if ($event->get('event') === 'charge.success') {
            $reference = data_get($event->all(), 'data.reference');

            if ($reference) {
                $this->payments->verifyAndComplete($reference);
            }
        }

        return response('OK', 200);
    }
}
