<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    private function paidOrderWithItem(int $stock = 8, int $qty = 2): Order
    {
        $order = Order::factory()->paid()->create();
        $product = Product::factory()->stock($stock)->create();
        $variant = $product->primaryVariant();
        $order->items()->create(['product_variant_id' => $variant->id, 'name' => $product->name, 'sku' => $variant->sku, 'unit_price' => 1000, 'quantity' => $qty, 'line_total' => 1000 * $qty]);
        $order->statusHistories()->create(['status' => OrderStatus::Paid, 'note' => 'Payment confirmed']);
        $order->payment()->create(['reference' => 'GB-PAY-REF', 'amount' => $order->total, 'status' => 'success', 'paid_at' => now()]);

        return $order;
    }

    public function test_admin_can_refund_a_paid_order_and_stock_is_restored(): void
    {
        $this->actingAsAdmin('Admin');
        Http::fake(['*/refund' => Http::response(['status' => true, 'data' => ['id' => 99]])]);

        $order = $this->paidOrderWithItem(stock: 8, qty: 2);
        $variantId = $order->items->first()->product_variant_id;

        $this->post(route('admin.orders.refund', $order), ['reason' => 'Customer request'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'refunded', 'status' => 'refunded']);
        $this->assertDatabaseHas('refunds', ['order_id' => $order->id, 'status' => 'succeeded', 'reason' => 'Customer request']);
        $this->assertSame(10, ProductVariant::find($variantId)->stock); // 8 + 2 restocked
    }

    public function test_failed_provider_refund_does_not_change_order(): void
    {
        $this->actingAsAdmin('Admin');
        Http::fake(['*/refund' => Http::response(['status' => false, 'message' => 'declined'])]);

        $order = $this->paidOrderWithItem();

        $this->post(route('admin.orders.refund', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('refunds', ['order_id' => $order->id, 'status' => 'failed']);
    }

    public function test_manager_cannot_issue_refunds(): void
    {
        $this->actingAsAdmin('Manager'); // Manager lacks manage_payments
        $order = $this->paidOrderWithItem();

        $this->post(route('admin.orders.refund', $order))->assertForbidden();
        $this->get(route('admin.payments.index'))->assertForbidden();
    }
}
