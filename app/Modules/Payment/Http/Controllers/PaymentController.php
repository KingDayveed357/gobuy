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

        $this->cart->clear();
        // NOTE: Stock reservations have a TTL and will expire automatically.
        // ReleaseInventoryForOrder listener handles cancellation releases.

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
        
        // Ensure we are using the raw body for HMAC check
        $expected = hash_hmac('sha512', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Paystack webhook signature mismatch');
            return response('Invalid signature', 401);
        }

        $payloadData = $request->json()->all();
        $eventType = data_get($payloadData, 'event', 'unknown');
        
        // Extract idempotency key safely
        $paystackId = data_get($payloadData, 'data.id');
        $idempotencyKey = $paystackId ? "{$eventType}_{$paystackId}" : hash('sha256', $signature . $eventType);

        // Phase 1: Fast transactional persistence (Outbox Pattern)
        \Illuminate\Support\Facades\DB::transaction(function () use ($eventType, $idempotencyKey, $payloadData) {
            $payload = \App\Modules\Payment\Models\WebhookPayload::firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'event_type' => $eventType,
                    'payload' => $payloadData,
                    'status' => 'pending',
                ]
            );

            // Phase 2: Dispatch async job safely upon commit
            if ($payload->wasRecentlyCreated) {
                \App\Modules\Payment\Jobs\ProcessWebhookJob::dispatch($payload)->afterCommit();
            } else {
                Log::info('Duplicate webhook skipped', ['idempotency_key' => $idempotencyKey]);
            }
        });

        // Always return 200 OK immediately
        return response('OK', 200);
    }
}
