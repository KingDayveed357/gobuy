<?php

namespace Tests\Feature\Returns;

use App\Admin\Models\Admin;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Services\RefundService;
use App\Modules\Returns\Services\StoreCreditService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RefundCreditSplitTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * An order paid ₦2,000 cash + ₦3,000 store credit (₦5,000 total).
     */
    private function mixedTenderOrder(User $user): Order
    {
        $product = Product::factory()->stock(10)->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Paid,
            'payment_status' => 'paid',
            'total' => Money::fromNaira(5000),
            'store_credit_applied' => Money::fromNaira(3000),
        ]);
        $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => $product->name, 'sku' => $product->primaryVariant()->sku,
            'unit_price' => Money::fromNaira(5000), 'quantity' => 1, 'line_total' => Money::fromNaira(5000),
        ]);
        // Gateway only ever collected the cash portion (amountDue).
        $order->payment()->create(['reference' => 'GB-MIX', 'amount' => Money::fromNaira(2000), 'status' => 'success', 'paid_at' => now()]);

        return $order;
    }

    public function test_full_refund_reverses_cash_via_gateway_and_returns_credit_to_wallet(): void
    {
        // Capture the kobo the gateway is asked to refund.
        Http::fake(['*/refund' => Http::response(['status' => true, 'data' => ['id' => 1]])]);
        $user = User::factory()->create();
        $order = $this->mixedTenderOrder($user);

        app(RefundService::class)->refund($order, Admin::factory()->create());

        // Gateway was reversed for the CASH portion only (₦2,000), never the full ₦5,000.
        Http::assertSent(fn ($request) => str_contains($request->url(), '/refund') && $request['amount'] === 200000);

        // The ₦3,000 store-credit portion came back to the wallet.
        $this->assertSame(300000, app(StoreCreditService::class)->balanceFor($user->fresh())->kobo);

        // Ledger: the money refund row is the cash portion; total refunded = full order.
        $this->assertDatabaseHas('refunds', ['order_id' => $order->id, 'amount' => 200000, 'status' => 'succeeded']);
        $this->assertSame(500000, (int) $order->fresh()->refunded_total->kobo);
        $this->assertSame('refunded', $order->fresh()->status->value);
    }

    public function test_a_second_refund_cannot_exceed_the_remaining_refundable(): void
    {
        Http::fake(['*/refund' => Http::response(['status' => true, 'data' => ['id' => 2]])]);
        $user = User::factory()->create();
        $order = $this->mixedTenderOrder($user);

        // Pretend ₦4,500 was already refunded earlier.
        $order->update(['refunded_total' => Money::fromNaira(4500)]);

        $this->expectExceptionMessage('cannot exceed the remaining refundable');
        app(RefundService::class)->refund($order, Admin::factory()->create(), Money::fromNaira(1000));
    }
}
