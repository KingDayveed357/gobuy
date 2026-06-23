<?php

namespace Tests\Feature\Returns;

use App\Admin\Models\Admin;
use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Events\ReturnSettled;
use App\Modules\Returns\Models\ReturnRequest;
use App\Modules\Returns\Services\ReturnSettlementService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ReturnObservabilityTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    private function receivedOriginalReturn(User $user): ReturnRequest
    {
        $product = Product::factory()->stock(5)->create();
        $order = Order::factory()->create([
            'user_id' => $user->id, 'status' => OrderStatus::Delivered, 'delivered_at' => now()->subDay(),
            'payment_status' => 'paid', 'total' => Money::fromNaira(4000),
        ]);
        $order->payment()->create(['reference' => 'GB-PAY-X', 'amount' => Money::fromNaira(4000), 'status' => 'success', 'paid_at' => now()]);
        $item = $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id, 'name' => $product->name,
            'sku' => $product->primaryVariant()->sku, 'unit_price' => Money::fromNaira(2000),
            'quantity' => 2, 'line_total' => Money::fromNaira(4000),
        ]);
        $return = ReturnRequest::factory()->create([
            'order_id' => $order->id, 'user_id' => $user->id,
            'status' => ReturnStatus::Received, 'refund_destination' => 'original',
        ]);
        $return->items()->create([
            'order_item_id' => $item->id, 'product_variant_id' => $product->primaryVariant()->id,
            'quantity' => 2, 'unit_price_snapshot' => Money::fromNaira(2000), 'reason_code' => 'changed_mind',
        ]);

        return $return->fresh('items');
    }

    public function test_admin_returns_index_shows_kpi_cards(): void
    {
        $this->actingAsAdmin('Super Admin');
        ReturnRequest::factory()->create(['status' => ReturnStatus::Requested]);

        $this->get(route('admin.returns.index'))
            ->assertOk()
            ->assertSee('Return rate')
            ->assertSee('Auto-approved');
    }

    public function test_a_failed_gateway_refund_aborts_settlement_and_alerts_admins(): void
    {
        // Gateway declines the refund.
        Http::fake(['*/refund' => Http::response(['status' => false, 'message' => 'declined'])]);
        $this->actingAsAdmin('Admin'); // has manage_refunds
        $user = User::factory()->create();
        $return = $this->receivedOriginalReturn($user);

        $this->post(route('admin.returns.settle', $return))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Nothing was settled — the return is still received, the order untouched.
        $this->assertSame('received', $return->fresh()->status->value);
        $this->assertSame(0, (int) $return->order->fresh()->refunded_total->kobo);

        // Admins were alerted.
        $this->assertTrue(
            DatabaseNotification::where('data->type', 'return_settlement_failed')->exists()
        );
    }

    public function test_settling_dispatches_the_return_settled_event(): void
    {
        Event::fake([ReturnSettled::class]);
        $user = User::factory()->create();
        $product = Product::factory()->stock(5)->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'total' => Money::fromNaira(2000)]);
        $item = $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id, 'name' => $product->name,
            'sku' => $product->primaryVariant()->sku, 'unit_price' => Money::fromNaira(2000),
            'quantity' => 1, 'line_total' => Money::fromNaira(2000),
        ]);
        $return = ReturnRequest::factory()->create(['order_id' => $order->id, 'user_id' => $user->id, 'status' => ReturnStatus::Received]);
        $return->items()->create([
            'order_item_id' => $item->id, 'product_variant_id' => $product->primaryVariant()->id,
            'quantity' => 1, 'unit_price_snapshot' => Money::fromNaira(2000), 'reason_code' => 'changed_mind',
        ]);

        app(ReturnSettlementService::class)->settle($return->fresh('items'), Admin::factory()->create());

        Event::assertDispatched(ReturnSettled::class);
    }
}
