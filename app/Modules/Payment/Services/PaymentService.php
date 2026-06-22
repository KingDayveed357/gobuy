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
    ) {}

    /**
     * Create a pending payment and hand back the provider's redirect URL.
     */
    public function initializeFor(Order $order, string $callbackUrl): string
    {
        $reference = 'GB-PAY-'.Str::upper(Str::random(12));

        $payment = $order->payment()->create([
            'reference' => $reference,
            'amount' => $order->total,
            'status' => 'pending',
        ]);

        $result = $this->gateway->initialize($order, $reference, $callbackUrl);

        $payment->update(['payload' => ['authorization_url' => $result['authorization_url']]]);

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
            }

            if ($paymentReceived) {
                $order->update(['payment_status' => PaymentStatus::Paid]);
            }
        });

        if ($firstAcceptance) {
            $this->notifyOrderAccepted($order);
        }
    }

    private function notifyOrderAccepted(Order $order): void
    {
        // Queued so the webhook/callback returns fast (the only async work in MVP).
        Mail::to($order->customer_email)->queue(new OrderConfirmationMail($order));

        // SMS/WhatsApp confirmation to the customer.
        $this->notifier->orderAccepted($order);

        // Alert admins who handle orders (can() is null-safe if RBAC is absent).
        $admins = Admin::where('is_active', true)->get()
            ->filter(fn (Admin $admin) => $admin->can('manage_orders'));
        Notification::send($admins, new NewPaidOrderNotification($order));
    }
}
