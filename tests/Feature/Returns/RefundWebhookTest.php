<?php

namespace Tests\Feature\Returns;

use App\Admin\Models\Admin;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\Refund;
use App\Modules\Payment\Services\RefundService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * The refund.processed / refund.failed webhook handlers. Refunds finalise
 * synchronously when the gateway accepts; these webhooks are the idempotent
 * reconciliation that finalises a refund a gateway timeout left unconfirmed.
 */
class RefundWebhookTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function fireWebhook(string $event, array $data): void
    {
        $content = json_encode(['event' => $event, 'data' => $data]);
        $signature = hash_hmac('sha512', $content, (string) config('services.paystack.secret_key'));

        $this->call('POST', '/payment/webhook', [], [], [], [
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $content)->assertOk();
    }

    /**
     * A paid order with a refund left 'processing' (gateway accepted it but the
     * confirming response never arrived).
     *
     * @return array{order: Order, refund: Refund, variant_id: int}
     */
    private function strandedRefund(): array
    {
        $product = Product::factory()->stock(8)->create();
        $variant = $product->primaryVariant();

        $order = Order::factory()->paid()->create(['total' => Money::fromNaira(2000)]);
        $order->items()->create([
            'product_variant_id' => $variant->id, 'name' => $product->name, 'sku' => $variant->sku,
            'unit_price' => Money::fromNaira(1000), 'quantity' => 2, 'line_total' => Money::fromNaira(2000),
        ]);
        $order->statusHistories()->create(['status' => 'paid', 'note' => 'Payment confirmed']);
        $order->payment()->create(['reference' => 'GB-PAY-X', 'amount' => Money::fromNaira(2000), 'status' => 'success', 'paid_at' => now()]);

        $refund = $order->refunds()->create([
            'payment_id' => $order->payment->id,
            'admin_id' => Admin::factory()->create()->id,
            'amount' => Money::fromNaira(2000),
            'status' => 'processing',
            'provider_reference' => 'RF-777',
            'payload' => ['completion' => 'order', 'total_amount_kobo' => 200000, 'credit_amount_kobo' => 0, 'refund_type' => 'full'],
        ]);

        return ['order' => $order, 'refund' => $refund, 'variant_id' => $variant->id];
    }

    public function test_refund_processed_webhook_finalises_a_stranded_refund(): void
    {
        ['order' => $order, 'refund' => $refund, 'variant_id' => $variantId] = $this->strandedRefund();

        $this->fireWebhook('refund.processed', ['id' => 'RF-777']);

        $this->assertDatabaseHas('refunds', ['id' => $refund->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'refunded', 'status' => 'refunded']);
        $this->assertSame(10, ProductVariant::find($variantId)->stock); // 8 + 2 restocked once
    }

    public function test_mark_confirmed_is_idempotent(): void
    {
        ['order' => $order, 'variant_id' => $variantId] = $this->strandedRefund();

        $refunds = app(RefundService::class);
        $refunds->markConfirmed('RF-777');
        $refunds->markConfirmed('RF-777'); // second call is a no-op

        $this->assertSame(10, ProductVariant::find($variantId)->stock); // restocked exactly once
        $this->assertSame(200000, (int) $order->fresh()->refunded_total->kobo); // ledger bumped once
    }

    public function test_refund_failed_webhook_marks_a_processing_refund_failed(): void
    {
        ['order' => $order, 'refund' => $refund, 'variant_id' => $variantId] = $this->strandedRefund();

        $this->fireWebhook('refund.failed', ['id' => 'RF-777']);

        $this->assertDatabaseHas('refunds', ['id' => $refund->id, 'status' => 'failed']);
        // Nothing was finalised: order untouched, stock not restored.
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
        $this->assertSame(8, ProductVariant::find($variantId)->stock);
    }
}
