<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\ReorderService;
use App\Modules\Returns\Models\StoreCreditEntry;
use App\Modules\Returns\Services\StoreCreditService;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReorderAndExpiryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function orderWith(User $user, Product $product, int $qty, int $paidNaira): Order
    {
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Delivered]);
        $order->items()->create([
            'product_variant_id' => $product->primaryVariant()->id,
            'name' => $product->name, 'sku' => $product->primaryVariant()->sku,
            'unit_price' => Money::fromNaira($paidNaira), 'quantity' => $qty,
            'line_total' => Money::fromNaira($paidNaira * $qty),
        ]);

        return $order;
    }

    public function test_preview_flags_a_price_change(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->priced(6000)->stock(10)->create();   // now ₦6,000
        $order = $this->orderWith($user, $product, 1, paidNaira: 5000);      // paid ₦5,000

        $preview = app(ReorderService::class)->preview($order, $user);
        $line = $preview['lines']->first();

        $this->assertSame(ReorderService::OK, $line['status']);
        $this->assertTrue($line['price_changed']);
        $this->assertSame(600000, $line['current']->kobo);
        $this->assertTrue($preview['has_changes']);
    }

    public function test_preview_caps_a_partial_line_and_flags_out_of_stock_and_unavailable(): void
    {
        $user = User::factory()->create();

        $low = Product::factory()->priced(2000)->stock(1)->create();
        $order = $this->orderWith($user, $low, 3, 2000); // wanted 3, only 1 left

        $preview = app(ReorderService::class)->preview($order, $user);
        $line = $preview['lines']->first();

        $this->assertSame(ReorderService::PARTIAL, $line['status']);
        $this->assertSame(1, $line['available']);
        $this->assertSame(1, $preview['addable']);
    }

    public function test_preview_page_renders_and_lets_you_add_available_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->priced(2000)->stock(5)->create();
        $order = $this->orderWith($user, $product, 2, 2000);

        $this->actingAs($user)->get(route('account.orders.reorder.preview', $order))
            ->assertOk()
            ->assertSee('Reorder from');

        $this->actingAs($user)->post(route('account.orders.reorder', $order))
            ->assertRedirect(route('cart.index'));
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $product->primaryVariant()->id]);
    }

    public function test_expired_store_credit_is_clawed_back_to_zero_for_the_unspent_portion(): void
    {
        $user = User::factory()->create();
        $service = app(StoreCreditService::class);

        // ₦5,000 granted, ₦2,000 spent → ₦3,000 unspent.
        $service->issue($user, Money::fromNaira(5000), null, 'grant-1');
        $service->spend($user, Money::fromNaira(2000), null, 'spend-1');

        // Backdate the grant's expiry and run the job.
        StoreCreditEntry::where('idempotency_key', 'grant-1')->update(['expires_at' => now()->subDay()]);
        $this->artisan('store-credit:expire')->assertSuccessful();

        // Only the unspent ₦3,000 expires.
        $this->assertSame(0, $service->balanceFor($user->fresh())->kobo);
        $this->assertDatabaseHas('store_credit_entries', ['type' => 'expiry', 'amount' => -300000]);

        // Re-running does nothing (idempotent).
        $this->artisan('store-credit:expire')->assertSuccessful();
        $this->assertSame(1, StoreCreditEntry::where('type', 'expiry')->count());
    }
}
