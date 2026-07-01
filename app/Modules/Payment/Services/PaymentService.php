<?php

namespace App\Modules\Payment\Services;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\CatalogService;
use App\Modules\Notification\Services\CustomerNotifier;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Events\OrderPaid;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderStatusService;
use App\Modules\Payment\Contracts\PaymentGateway;
use App\Modules\Payment\Models\Payment;
use App\Modules\Pricing\Services\CouponService;
use App\Modules\Returns\Services\StoreCreditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly OrderStatusService $status,
        private readonly CatalogService $catalog,
        private readonly CustomerNotifier $notifier,
        private readonly StoreCreditService $storeCredit,
        private readonly CouponService $coupons,
    ) {}

    /**
     * Create a pending payment and hand back the provider's redirect URL.
     */
    public function initializeFor(Order $order, string $callbackUrl): string
    {
        $key = 'init_payment_'.$order->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw new \RuntimeException('Too many payment initialization attempts. Please wait a minute before trying again.');
        }

        RateLimiter::hit($key, 60);

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

        // Amount + currency assertion — prevent partial/manipulated amounts and
        // cross-currency settlement against an NGN-priced order.
        $gatewayAmount = data_get($result['raw'], 'data.amount');
        $gatewayCurrency = data_get($result['raw'], 'data.currency');
        $expectedCurrency = (string) config('services.paystack.currency', 'NGN');

        $amountMismatch = $gatewayAmount !== null && (int) $gatewayAmount !== $payment->amount->kobo;
        $currencyMismatch = $gatewayCurrency !== null && $gatewayCurrency !== $expectedCurrency;

        if ($amountMismatch || $currencyMismatch) {
            Log::critical('Payment amount/currency mismatch detected', [
                'reference' => $reference,
                'order_number' => $payment->order->order_number,
                'expected_kobo' => $payment->amount->kobo,
                'actual_kobo' => $gatewayAmount,
                'expected_currency' => $expectedCurrency,
                'actual_currency' => $gatewayCurrency,
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
     * Admin override: manually mark a pending payment as paid and complete its
     * order. Use only when funds are confirmed received out of band (e.g. a
     * webhook never arrived). completeOrder is idempotent and row-locked, so a
     * racing webhook for the same reference cannot double-apply side effects.
     */
    public function markPaidManually(Payment $payment, ?Admin $admin = null): void
    {
        // Refuse if the order is already paid by another reference — confirming a
        // second reference would create two "success" rows (a double collection).
        if ($payment->status !== 'pending' || ! $payment->order || $payment->order->isPaid()) {
            return;
        }

        $payment->update([
            'status' => 'success',
            'paid_at' => now(),
            'payload' => array_merge($payment->payload ?? [], [
                'manual_confirmation' => true,
                'confirmed_by_admin_id' => $admin?->id,
            ]),
        ]);

        $this->completeOrder($payment->order, paymentReceived: true);

        Log::info('Payment manually confirmed by admin', [
            'reference' => $payment->reference,
            'order_number' => $payment->order->order_number,
            'admin_id' => $admin?->id,
        ]);
    }

    /**
     * The single place an order transitions from "placed" to "accepted":
     * commits stock, redeems the coupon, spends store credit, and advances the
     * order. Idempotent and concurrency-safe — the order row is locked and the
     * "first acceptance" decision is made under that lock, so a browser callback
     * and a webhook racing on the same payment can never both run the side
     * effects (double stock decrement / double events).
     */
    public function completeOrder(Order $order, bool $paymentReceived = true): void
    {
        $firstAcceptance = DB::transaction(function () use ($order, $paymentReceived): bool {
            // Lock the order row; decide acceptance from the locked state, not
            // from the (possibly stale) in-memory model passed by the caller.
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $firstAcceptance = $locked->status === OrderStatus::Pending;

            if ($firstAcceptance) {
                $this->status->transitionTo(
                    $order,
                    OrderStatus::Paid,
                    $paymentReceived ? 'Payment confirmed' : 'Order accepted (Pay on Delivery)',
                );

                // Redeem the coupon snapshotted at placement now that the order is
                // genuinely accepted — abandoned/cancelled payments never consume a
                // coupon's usage limit. Idempotent per order_id.
                if ($order->coupon) {
                    $this->coupons->redeem($order->coupon, $order->user, $order, $order->discount_amount);
                }

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

            return $firstAcceptance;
        });

        if ($firstAcceptance) {
            OrderPaid::dispatch($order);
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

        // Only reflect failure on the order if it is NOT already paid by another
        // reference. A redundant/abandoned attempt must never downgrade a paid
        // order (which would leave status=Paid + payment_status=Failed).
        if (! $payment->order->isPaid()) {
            $payment->order->update(['payment_status' => PaymentStatus::Failed]);
        }

        Log::warning('Payment marked failed', [
            'reference' => $reference,
            'order_number' => $payment->order->order_number,
            'order_already_paid' => $payment->order->isPaid(),
        ]);
    }

    /**
     * Admin override: declare a pending payment dead and cancel its order in one
     * step, so the order and payment never sit in a contradictory half-state
     * (payment failed but order still "pending"). No stock was committed for a
     * pending payment, so there is nothing to release.
     */
    public function failAndCancelOrder(Payment $payment, ?Admin $admin = null): void
    {
        if ($payment->status !== 'pending') {
            return;
        }

        $this->markFailed($payment->reference, [
            'manual_failure' => true,
            'failed_by_admin_id' => $admin?->id,
        ]);

        $order = $payment->order;

        if ($order && $order->status === OrderStatus::Pending) {
            $this->status->transitionTo($order, OrderStatus::Cancelled, 'Payment marked failed by admin');
        }
    }
}
