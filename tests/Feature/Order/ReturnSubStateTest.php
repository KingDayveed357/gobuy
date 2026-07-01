<?php

namespace Tests\Feature\Order;

use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReturnSubStateTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Build an order carrying a single line of the given quantity.
     */
    private function orderWithLine(int $quantity, int $returned = 0): Order
    {
        $product = Product::factory()->stock(20)->create();
        $order = Order::factory()->create([
            'status' => OrderStatus::Completed,
            'payment_status' => 'paid',
            'total' => Money::fromNaira(5000),
        ]);
        $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => $product->name,
            'sku' => $product->primaryVariant()->sku,
            'unit_price' => Money::fromNaira(5000),
            'quantity' => $quantity,
            'returned_quantity' => $returned,
            'line_total' => Money::fromNaira(5000 * $quantity),
        ]);

        return $order->fresh();
    }

    public function test_an_order_with_no_returns_reports_no_sub_state(): void
    {
        $order = $this->orderWithLine(quantity: 3);

        $this->assertSame('none', $order->returnState());
        $this->assertFalse($order->hasReturns());
        $this->assertNull($order->returnStateLabel());
    }

    public function test_a_partially_returned_order_is_flagged_without_changing_status(): void
    {
        $order = $this->orderWithLine(quantity: 3, returned: 1);

        $this->assertSame('partially_returned', $order->returnState());
        $this->assertTrue($order->hasReturns());
        $this->assertSame('Partially returned', $order->returnStateLabel());
        // The status enum itself is untouched — the sub-state is derived, not stored.
        $this->assertSame(OrderStatus::Completed, $order->status);
    }

    public function test_a_fully_returned_order_reports_the_returned_sub_state(): void
    {
        $order = $this->orderWithLine(quantity: 2, returned: 2);

        $this->assertSame('returned', $order->returnState());
        $this->assertSame('Returned', $order->returnStateLabel());
    }
}
