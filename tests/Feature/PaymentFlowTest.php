<?php

namespace Tests\Feature;

use App\Modules\Cart\Models\CartItem;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\Payment;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function placePendingOrder(int $stock = 10, int $qty = 2): Payment
    {
        Http::fake(['*/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://paystack.test/redirect']])]);

        $product = Product::factory()->create(['stock' => $stock, 'retail_price' => 5000]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => $qty]);
        $this->post(route('checkout.store'), [
            'customer_name' => 'Ada Obi',
            'customer_email' => 'ada@example.com',
            'customer_phone' => '08030000000',
            'address_line' => '12 Marina Road',
            'city' => 'Lagos',
            'state' => 'Lagos',
        ]);

        return Payment::with('order')->firstOrFail();
    }

    public function test_successful_callback_marks_order_paid_decrements_stock_and_clears_cart(): void
    {
        $payment = $this->placePendingOrder(stock: 10, qty: 2);
        $productId = $payment->order->items->first()->product_id;

        Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success']])]);

        $this->get(route('payment.callback', ['reference' => $payment->reference]))
            ->assertRedirect(route('orders.success', $payment->order));

        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'status' => 'paid', 'payment_status' => 'paid']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'success']);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $payment->order_id, 'status' => 'paid']);
        $this->assertSame(8, Product::find($productId)->stock);
        $this->assertSame(0, CartItem::count());
    }

    public function test_failed_verification_does_not_mark_order_paid(): void
    {
        $payment = $this->placePendingOrder();

        Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'failed']])]);

        $this->get(route('payment.callback', ['reference' => $payment->reference]));

        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'payment_status' => 'failed', 'status' => 'pending']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $content = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'whatever']]);

        $this->call('POST', '/payment/webhook', [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => 'wrong',
            'CONTENT_TYPE' => 'application/json',
        ], $content)->assertStatus(401);
    }

    public function test_webhook_processes_charge_success_idempotently(): void
    {
        $payment = $this->placePendingOrder(stock: 10, qty: 2);
        $productId = $payment->order->items->first()->product_id;

        Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success']])]);

        $content = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
        $secret = (string) config('services.paystack.secret_key');
        $signature = hash_hmac('sha512', $content, $secret);

        // Fire the webhook twice — stock must only be decremented once.
        for ($i = 0; $i < 2; $i++) {
            $this->call('POST', '/payment/webhook', [], [], [], [
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ], $content)->assertOk();
        }

        $this->assertDatabaseHas('orders', ['id' => $payment->order_id, 'payment_status' => 'paid']);
        $this->assertSame(8, Product::find($productId)->stock);
    }

    public function test_paid_order_shows_success_receipt(): void
    {
        $order = Order::factory()->paid()->create();
        $order->items()->create(['product_id' => null, 'name' => 'Sample', 'sku' => 'S1', 'unit_price' => 1000, 'quantity' => 1, 'line_total' => 1000]);
        $order->statusHistories()->create(['status' => OrderStatus::Paid, 'note' => 'Payment confirmed']);

        $this->get(route('orders.success', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Thank you for your order');
    }
}
