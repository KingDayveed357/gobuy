<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Logistics\Database\Seeders\LogisticsSeeder;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\BankTransferProof;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class PaymentMethodsTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LogisticsSeeder::class);
        Mail::fake();
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(string $paymentMethod): array
    {
        return [
            'customer_name' => 'Ada Obi', 'customer_email' => 'ada@example.com', 'customer_phone' => '0803',
            'delivery_method' => 'home_delivery', 'payment_method' => $paymentMethod,
            'address_line' => '12 Marina', 'city' => 'Lagos', 'state' => 'Lagos',
        ];
    }

    private function cartWith(int $naira, int $qty = 2, int $stock = 20): Product
    {
        $product = Product::factory()->priced($naira)->stock($stock)->create(['weight_g' => 500]);
        $this->post(route('cart.store'), ['product_variant_id' => $product->primaryVariant()->id, 'quantity' => $qty]);

        return $product;
    }

    public function test_bank_transfer_places_order_unpaid_without_decrementing_stock(): void
    {
        $product = $this->cartWith(naira: 10000);

        $this->post(route('checkout.store'), $this->checkoutPayload('bank_transfer'))
            ->assertRedirectContains('/transfer');

        $order = Order::latest('id')->first();
        $this->assertSame(PaymentMethod::BankTransfer, $order->payment_method);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(20, $product->primaryVariant()->fresh()->stock); // not yet committed
    }

    public function test_customer_can_upload_proof_and_admin_confirmation_completes_the_order(): void
    {
        Storage::fake('public');
        $product = $this->cartWith(naira: 10000);
        $this->post(route('checkout.store'), $this->checkoutPayload('bank_transfer'));
        $order = Order::latest('id')->first();

        $this->post(route('orders.transfer.store', $order), [
            'amount' => 20000,
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertRedirect(route('orders.transfer.show', $order));

        $proof = $order->transferProofs()->first();
        $this->assertSame(1, $proof->getMedia(BankTransferProof::MEDIA_RECEIPT)->count());

        $this->actingAsAdmin('Admin');
        $this->post(route('admin.transfers.approve', $proof))->assertRedirect();

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame('approved', $proof->fresh()->status);
        $this->assertSame(18, $product->primaryVariant()->fresh()->stock); // committed on confirm
    }

    public function test_pay_on_delivery_confirms_order_and_commits_stock_but_leaves_payment_outstanding(): void
    {
        $product = $this->cartWith(naira: 10000); // ₦20,000 subtotal, under threshold

        $this->post(route('checkout.store'), $this->checkoutPayload('pod'))
            ->assertRedirect(route('orders.success', Order::latest('id')->first()));

        $order = Order::latest('id')->first();
        $this->assertSame(PaymentMethod::PayOnDelivery, $order->payment_method);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertSame(OrderStatus::Paid, $order->status); // accepted for fulfilment
        $this->assertSame(18, $product->primaryVariant()->fresh()->stock);
    }

    public function test_pay_on_delivery_is_rejected_above_threshold(): void
    {
        $this->cartWith(naira: 100000); // ₦200,000 subtotal, over ₦150,000 threshold

        $this->post(route('checkout.store'), $this->checkoutPayload('pod'))
            ->assertRedirect(route('checkout.show'))
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
    }

    public function test_admin_can_record_pod_cash_collection(): void
    {
        $this->cartWith(naira: 10000);
        $this->post(route('checkout.store'), $this->checkoutPayload('pod'));
        $order = Order::latest('id')->first();

        $this->actingAsAdmin('Admin');
        $this->post(route('admin.orders.pod-collected', $order))->assertRedirect();

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    public function test_partial_refund_returns_money_without_restocking(): void
    {
        Http::fake(['*/refund' => Http::response(['status' => true, 'data' => ['id' => 1]])]);

        $variant = ProductVariant::factory()->create(['stock' => 5]);
        $order = Order::factory()->paid()->create();
        $order->payment()->create(['reference' => 'GB-PAY-X', 'amount' => $order->total, 'status' => 'success']);
        $order->items()->create([
            'product_variant_id' => $variant->id, 'name' => 'Item', 'sku' => $variant->sku,
            'unit_price' => Money::fromNaira(5000), 'quantity' => 2, 'line_total' => Money::fromNaira(10000),
        ]);

        $this->actingAsAdmin('Admin');
        $this->post(route('admin.orders.refund', $order), ['amount' => 2000])->assertRedirect();

        $this->assertDatabaseHas('refunds', ['order_id' => $order->id, 'amount' => Money::fromNaira(2000)->kobo, 'status' => 'succeeded']);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $order->fresh()->payment_status); // partial, not fully refunded
        $this->assertSame(5, $variant->fresh()->stock); // partial refund does not restock
    }
}
