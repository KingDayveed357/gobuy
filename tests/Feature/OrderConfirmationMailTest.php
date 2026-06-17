<?php

namespace Tests\Feature;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Mail\OrderConfirmationMail;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderConfirmationMailTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_confirmation_email_is_queued_on_successful_payment(): void
    {
        Mail::fake();
        Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success']])]);

        $order = Order::factory()->create(['customer_email' => 'buyer@example.com']);
        $order->items()->create(['product_id' => null, 'name' => 'Item', 'sku' => 'I1', 'unit_price' => 1000, 'quantity' => 1, 'line_total' => 1000]);
        $order->statusHistories()->create(['status' => OrderStatus::Pending, 'note' => 'Order placed']);
        $payment = $order->payment()->create(['reference' => 'GB-PAY-MAILTEST', 'amount' => $order->total, 'status' => 'pending']);

        app(PaymentService::class)->verifyAndComplete($payment->reference);

        Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order) {
            return $mail->order->is($order) && $mail->hasTo('buyer@example.com');
        });
    }
}
