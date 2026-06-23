<?php

namespace Tests\Feature\Returns;

use App\Admin\Models\Admin;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Returns\Enums\ReturnItemDisposition;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Models\ReturnRequest;
use App\Modules\Returns\Models\StoreCreditEntry;
use App\Modules\Returns\Services\ReturnSettlementService;
use App\Modules\Returns\Services\StoreCreditService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ReturnSettlementTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    /**
     * Build a received return for one product line.
     *
     * @param  array<string, mixed>  $opts
     * @return array{return: ReturnRequest, order: Order, variant: ProductVariant}
     */
    private function receivedReturn(User $user, array $opts = []): array
    {
        $subtotal = $opts['subtotal'] ?? 4000;
        $product = Product::factory()->stock($opts['stock'] ?? 5)->create();
        $variant = $product->primaryVariant();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::Delivered,
            'delivered_at' => now()->subDay(),
            'payment_status' => $opts['paid'] ?? false ? 'paid' : 'unpaid',
            'subtotal' => Money::fromNaira($subtotal),
            'discount_amount' => Money::fromNaira($opts['discount'] ?? 0),
            'total' => Money::fromNaira($opts['total'] ?? $subtotal),
            'refunded_total' => Money::fromNaira($opts['already_refunded'] ?? 0),
        ]);

        if (! empty($opts['paid'])) {
            $order->payment()->create(['reference' => 'GB-REF-'.uniqid(), 'amount' => $order->total, 'status' => 'success', 'paid_at' => now()]);
        }

        $orderItem = $order->items()->create([
            'product_variant_id' => $variant->id,
            'name' => $product->name,
            'sku' => $variant->sku,
            'unit_price' => Money::fromNaira($opts['unit_price'] ?? 2000),
            'quantity' => $opts['quantity'] ?? 2,
            'line_total' => Money::fromNaira(($opts['unit_price'] ?? 2000) * ($opts['quantity'] ?? 2)),
        ]);

        $return = ReturnRequest::factory()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => ReturnStatus::Received,
            'refund_destination' => $opts['destination'] ?? 'store_credit',
        ]);
        $return->items()->create([
            'order_item_id' => $orderItem->id,
            'product_variant_id' => $variant->id,
            'quantity' => $opts['return_qty'] ?? 2,
            'unit_price_snapshot' => Money::fromNaira($opts['unit_price'] ?? 2000),
            'reason_code' => 'changed_mind',
            'disposition' => $opts['disposition'] ?? null,
        ]);

        return ['return' => $return->fresh('items'), 'order' => $order, 'variant' => $variant];
    }

    public function test_settling_to_store_credit_credits_the_wallet_and_restocks(): void
    {
        $user = User::factory()->create();
        ['return' => $return, 'order' => $order, 'variant' => $variant] = $this->receivedReturn($user, [
            'subtotal' => 4000, 'total' => 4000, 'unit_price' => 2000, 'quantity' => 2, 'return_qty' => 2, 'stock' => 5,
        ]);

        $result = app(ReturnSettlementService::class)->settle($return, Admin::factory()->create());

        $this->assertTrue($result['settled']);
        $this->assertSame(400000, $result['amount']->kobo);
        $this->assertSame(400000, app(StoreCreditService::class)->balanceFor($user)->kobo);
        $this->assertSame('closed', $return->fresh()->status->value);
        $this->assertSame(400000, (int) $order->fresh()->refunded_total->kobo);
        $this->assertSame(2, $order->items->first()->fresh()->returned_quantity);
        $this->assertSame(7, $variant->fresh()->stock); // 5 + 2 restocked
        $this->assertDatabaseHas('store_credit_entries', ['type' => 'refund_credit', 'amount' => 400000]);
    }

    public function test_settling_to_original_method_refunds_via_the_gateway(): void
    {
        Http::fake(['*/refund' => Http::response(['status' => true, 'data' => ['id' => 77]])]);
        $user = User::factory()->create();
        ['return' => $return, 'order' => $order] = $this->receivedReturn($user, [
            'paid' => true, 'destination' => 'original', 'subtotal' => 4000, 'total' => 4000, 'return_qty' => 2,
        ]);

        $result = app(ReturnSettlementService::class)->settle($return, Admin::factory()->create());

        $this->assertSame('original', $result['via']);
        $this->assertDatabaseHas('refunds', ['order_id' => $order->id, 'status' => 'succeeded', 'amount' => 400000]);
        $this->assertSame(400000, (int) $order->fresh()->refunded_total->kobo);
        $this->assertSame('closed', $return->fresh()->status->value);
    }

    public function test_partial_return_prorates_the_order_discount(): void
    {
        $user = User::factory()->create();
        // ₦10,000 subtotal, ₦1,000 coupon (10% off), return one ₦2,000 unit.
        ['return' => $return] = $this->receivedReturn($user, [
            'subtotal' => 10000, 'discount' => 1000, 'total' => 9000,
            'unit_price' => 2000, 'quantity' => 5, 'return_qty' => 1,
        ]);

        $result = app(ReturnSettlementService::class)->settle($return, Admin::factory()->create());

        // 2000 * (9000/10000) = 1800 → 180,000 kobo.
        $this->assertSame(180000, $result['amount']->kobo);
        $this->assertSame(180000, app(StoreCreditService::class)->balanceFor($user)->kobo);
    }

    public function test_settlement_is_capped_at_the_order_remaining_refundable(): void
    {
        $user = User::factory()->create();
        // Order total ₦4,000 but ₦3,000 already refunded earlier → only ₦1,000 left.
        ['return' => $return] = $this->receivedReturn($user, [
            'subtotal' => 4000, 'total' => 4000, 'already_refunded' => 3000,
            'unit_price' => 2000, 'quantity' => 2, 'return_qty' => 2,
        ]);

        $result = app(ReturnSettlementService::class)->settle($return, Admin::factory()->create());

        $this->assertSame(100000, $result['amount']->kobo); // capped at ₦1,000
    }

    public function test_settlement_is_idempotent(): void
    {
        $user = User::factory()->create();
        ['return' => $return] = $this->receivedReturn($user, ['subtotal' => 4000, 'total' => 4000, 'return_qty' => 2]);
        $admin = Admin::factory()->create();

        app(ReturnSettlementService::class)->settle($return, $admin);
        $balanceAfterFirst = app(StoreCreditService::class)->balanceFor($user)->kobo;

        $second = app(ReturnSettlementService::class)->settle($return->fresh(), $admin);

        $this->assertFalse($second['settled']);
        $this->assertSame($balanceAfterFirst, app(StoreCreditService::class)->balanceFor($user)->kobo);
        $this->assertSame(1, $return->fresh()->order->refunds()->count() + StoreCreditEntry::count());
    }

    public function test_a_damaged_item_is_refunded_but_not_restocked(): void
    {
        $user = User::factory()->create();
        ['return' => $return, 'variant' => $variant] = $this->receivedReturn($user, [
            'subtotal' => 4000, 'total' => 4000, 'return_qty' => 2, 'stock' => 5,
            'disposition' => ReturnItemDisposition::Damaged,
        ]);

        $result = app(ReturnSettlementService::class)->settle($return, Admin::factory()->create());

        $this->assertSame(400000, $result['amount']->kobo);   // still refunded
        $this->assertSame(5, $variant->fresh()->stock);       // NOT restocked
    }

    public function test_a_rejected_item_yields_no_refund(): void
    {
        $user = User::factory()->create();
        ['return' => $return] = $this->receivedReturn($user, [
            'subtotal' => 4000, 'total' => 4000, 'return_qty' => 2,
            'disposition' => ReturnItemDisposition::Reject,
        ]);

        $result = app(ReturnSettlementService::class)->settle($return, Admin::factory()->create());

        $this->assertFalse($result['settled']);
        $this->assertSame('rejected', $return->fresh()->status->value);
        $this->assertSame(0, app(StoreCreditService::class)->balanceFor($user)->kobo);
    }
}
