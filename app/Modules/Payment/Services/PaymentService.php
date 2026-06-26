<?php

namespace App\Modules\Payment\Services;

use App\Admin\Models\Admin;
use App\Admin\Notifications\NewPaidOrderNotification;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Notification\Services\CustomerNotifier;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Mail\OrderConfirmationMail;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStatusService;
use App\Modules\Payment\Contracts\PaymentGateway;
use App\Modules\Payment\Models\Payment;
use App\Modules\Returns\Services\StoreCreditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly OrderStatusService $status,
        private readonly CatalogService $catalog,
        private readonly CustomerNotifier $notifier,
        private readonly StoreCreditService $storeCredit,
    ) {}

    /**
     * Create a pending payment and hand back the provider's redirect URL.
     */
    public function initializeFor(Order $order, string $callbackUrl): string
    {
        $key = 'init_payment_'.$order->id;

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) {
            throw new \RuntimeException('Too many payment initialization attempts. Please wait a minute before trying again.');
        }

        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        $reference = 'GB-PAY-'.Str::upper(Str::random(12));

        // Call the gateway FIRST. If it times out or fails, no orphaned record is created.
        $result = $this->gateway->initialize($order, $reference, $callbackUrl);

        // Write to DB only after the HTTP call succeeds.
        DB::transaction(function () use ($order, $reference, $result) {
            $order->payment()->create([
                'reference' => $reference,
                'amount' => $order->amountDue(), // net of any store credit tendered
                'status' => 'pending',
                'payload' => ['authorization_url' => $result['authorization_url']],
            ]);
        });

        return $result['authorization_url'];
    }

    /**
     * Verify a reference with the provider and complete the order if paid.
     * Idempotent: safe to call from both the browser callback and the webhook.
     */
    public function verifyAndComplete(string $reference): bool
    {
        $payment = Payment::with('order')->firstWhere('reference', $reference);

        if (! $payment || ! $payment->order) {
            Log::warning('Payment verification for unknown reference', ['reference' => $reference]);

            return false;
        }

        if ($payment->order->isPaid()) {
            return true; // already processed
        }

        $result = $this->gateway->verify($reference);

        if (! $result['success']) {
            $payment->update(['status' => 'failed', 'payload' => $result['raw']]);
            $payment->order->update(['payment_status' => PaymentStatus::Failed]);

            Log::warning('Payment verification failed', [
                'reference' => $reference,
                'order_number' => $payment->order->order_number,
            ]);

            return false;
        }

        // Amount assertion - prevent partial payments or manipulated amounts
        $gatewayAmount = data_get($result['raw'], 'data.amount');
        if ($gatewayAmount !== null && (int) $gatewayAmount !== $payment->amount->kobo) {
            Log::critical('Payment amount mismatch detected', [
                'reference' => $reference,
                'order_number' => $payment->order->order_number,
                'expected_kobo' => $payment->amount->kobo,
                'actual_kobo' => $gatewayAmount,
            ]);

            $payment->update(['status' => 'failed', 'payload' => $result['raw']]);
            $payment->order->update(['payment_status' => PaymentStatus::Failed]);

            return false;
        }

        $payment->update(['status' => 'success', 'paid_at' => now(), 'payload' => $result['raw']]);
        $this->completeOrder($payment->order, paymentReceived: true);

        Log::info('Payment successful', ['reference' => $reference, 'order_number' => $payment->order->order_number]);

        return true;
    }

    /**
     * Accept a Pay-on-Delivery order: commit stock and confirm the order, but
     * leave payment outstanding until cash is collected on delivery.
     */
    public function placePodOrder(Order $order): void
    {
        $this->completeOrder($order, paymentReceived: false);

        Log::info('POD order accepted', ['order_number' => $order->order_number]);
    }

    /**
     * Mark a Pay-on-Delivery order as paid once cash has been collected.
     */
    public function markPodCollected(Order $order): void
    {
        $order->update(['payment_status' => PaymentStatus::Paid]);

        Log::info('POD payment collected', ['order_number' => $order->order_number]);
    }

    /**
     * Confirm a manually-reconciled bank transfer: completes the order exactly
     * like an online payment would.
     */
    public function confirmManualPayment(Order $order): void
    {
        $this->completeOrder($order, paymentReceived: true);

        Log::info('Manual bank transfer confirmed', ['order_number' => $order->order_number]);
    }

    /**
     * The single place an order transitions from "placed" to "accepted":
     * commits stock, advances the order, and notifies. Idempotent — the
     * stock/transition only runs while the order is still pending.
     */
    public function completeOrder(Order $order, bool $paymentReceived = true): void
    {
        $firstAcceptance = $order->status === OrderStatus::Pending;

        DB::transaction(function () use ($order, $paymentReceived, $firstAcceptance): void {
            if ($firstAcceptance) {
                $this->status->transitionTo(
                    $order,
                    OrderStatus::Paid,
                    $paymentReceived ? 'Payment confirmed' : 'Order accepted (Pay on Delivery)',
                );

                foreach ($order->items as $item) {
                    $variant = $item->product_variant_id ? ProductVariant::find($item->product_variant_id) : null;

                    if ($variant) {
                        $this->catalog->decrementStock($variant, $item->quantity);
                    }
                }

                // Consume any store credit tendered against this order — exactly
                // once, only when the order is actually accepted (so abandoned
                // gateway payments never burn the customer's credit). Capped at
                // the live balance so it can't throw, and idempotent on the key.
                if ($order->store_credit_applied?->isPositive() && $order->user) {
                    $spendable = $this->storeCredit->redeemableFor($order->user, $order->store_credit_applied);
                    if ($spendable->isPositive()) {
                        $this->storeCredit->spend(
                            $order->user,
                            $spendable,
                            $order,
                            "order-spend:{$order->id}",
                            "Applied to order {$order->order_number}",
                        );
                    }
                }
            }

            if ($paymentReceived) {
                $order->update(['payment_status' => PaymentStatus::Paid]);
            }
        });

        if ($firstAcceptance) {
            \App\Modules\Order\Events\OrderPaid::dispatch($order);
        }
    }

    public function markFailed(string $reference, array $payload = []): void
    {
        $payment = Payment::with('order')->firstWhere('reference', $reference);

        if (! $payment || $payment->status !== 'pending') {
            return;
        }

        $payment->update([
            'status' => 'failed',
            'payload' => array_merge($payment->payload ?? [], $payload),
        ]);

        $payment->order->update(['payment_status' => PaymentStatus::Failed]);

        Log::warning('Payment failed via webhook', [
            'reference' => $reference,
            'order_number' => $payment->order->order_number,
        ]);
    }

}
