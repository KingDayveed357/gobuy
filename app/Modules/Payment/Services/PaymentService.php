<?php

namespace App\Modules\Payment\Services;

use App\Admin\Models\Admin;
use App\Admin\Notifications\NewPaidOrderNotification;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\CatalogService;
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

        $this->markPaid($payment, $result['raw']);

        return true;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function markPaid(Payment $payment, array $raw): void
    {
        DB::transaction(function () use ($payment, $raw): void {
            $order = $payment->order;

            $payment->update(['status' => 'success', 'paid_at' => now(), 'payload' => $raw]);
            $order->update(['payment_status' => PaymentStatus::Paid]);
            $this->status->transitionTo($order, OrderStatus::Paid, 'Payment confirmed');

            foreach ($order->items as $item) {
                $product = $item->product_id ? Product::find($item->product_id) : null;

                if ($product) {
                    $this->catalog->decrementStock($product, $item->quantity);
                }
            }
        });

        Log::info('Payment successful', [
            'reference' => $payment->reference,
            'order_number' => $payment->order->order_number,
        ]);

        // Queued so the webhook/callback returns fast (the only async work in MVP).
        Mail::to($payment->order->customer_email)->queue(new OrderConfirmationMail($payment->order));

        // Alert admins who handle orders (can() is null-safe if RBAC is absent).
        $admins = Admin::where('is_active', true)->get()
            ->filter(fn (Admin $admin) => $admin->can('manage_orders'));
        Notification::send($admins, new NewPaidOrderNotification($payment->order));
    }
}
