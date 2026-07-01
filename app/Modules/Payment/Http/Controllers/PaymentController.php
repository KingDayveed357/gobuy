<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Admin\Models\Admin;
use App\Admin\Notifications\AdminAlertNotification;
use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Order\Services\CheckoutCalculator;
use App\Modules\Payment\Jobs\ProcessWebhookJob;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\WebhookPayload;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Pricing\Services\CouponService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
            // Payment confirmed → tear down checkout state exactly once, here on
            // the browser return (the webhook has no access to this session).
            $this->cart->clear();
            session()->forget([CouponService::SESSION_KEY, CheckoutCalculator::CREDIT_SESSION_KEY]);

            return redirect()->route('orders.success', $payment->order)
                ->with('status', 'Payment successful. Thank you for your order!');
        }

        // Payment not completed — deliberately keep the cart AND the applied
        // coupon/store-credit session so the shopper can retry without losing
        // their checkout state. Stock reservations expire on their own TTL.
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

            // Alert admins, but at most once per 5 minutes so a flood of forged
            // requests can't spam the notification channel.
            if (Cache::add('webhook-sig-alert', true, 300)) {
                Notification::send(
                    Admin::withAbility('manage_payments'),
                    new AdminAlertNotification(
                        'Webhook signature failure',
                        'A Paystack webhook was rejected due to an invalid signature. If this repeats, verify the webhook secret and watch for tampering.',
                        'critical',
                        route('admin.payments.index'),
                    ),
                );
            }

            return response('Invalid signature', 401);
        }

        $payloadData = $request->json()->all();
        $eventType = data_get($payloadData, 'event', 'unknown');

        // Extract idempotency key safely
        $paystackId = data_get($payloadData, 'data.id');
        $idempotencyKey = $paystackId ? "{$eventType}_{$paystackId}" : hash('sha256', $signature.$eventType);

        // Phase 1: Fast transactional persistence (Outbox Pattern)
        DB::transaction(function () use ($eventType, $idempotencyKey, $payloadData) {
            // createOrFirst is race-safe (relies on the unique idempotency_key
            // index) where firstOrCreate's select-then-insert can double-insert
            // under concurrent identical webhooks.
            $payload = WebhookPayload::createOrFirst(
                ['idempotency_key' => $idempotencyKey],
                [
                    'event_type' => $eventType,
                    'payload' => $payloadData,
                    'status' => 'pending',
                ]
            );

            // Phase 2: Dispatch async job safely upon commit
            if ($payload->wasRecentlyCreated) {
                ProcessWebhookJob::dispatch($payload)->afterCommit();
            } else {
                Log::info('Duplicate webhook skipped', ['idempotency_key' => $idempotencyKey]);
            }
        });

        // Always return 200 OK immediately
        return response('OK', 200);
    }
}
