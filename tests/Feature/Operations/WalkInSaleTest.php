<?php

namespace Tests\Feature\Operations;

use App\Livewire\Admin\WalkIn\WalkInSale;
use App\Models\Setting;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Operations\WalkIn\Services\WalkInSaleService;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class WalkInSaleTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
        Setting::put('modules.ops.walk_in', '1'); // enable the module
    }

    public function test_the_sale_screen_is_reachable_when_the_module_is_on(): void
    {
        $this->get(route('admin.walk-in.index'))
            ->assertOk()
            ->assertSeeLivewire(WalkInSale::class);
    }

    public function test_the_sale_screen_404s_when_the_module_is_off(): void
    {
        Setting::put('modules.ops.walk_in', '0');

        $this->get(route('admin.walk-in.index'))->assertNotFound();
    }

    public function test_recording_a_walk_in_sale_creates_a_paid_order_and_deducts_stock(): void
    {
        $variant = Product::factory()->stock(10)->priced(1000)->create()->primaryVariant();

        $order = app(WalkInSaleService::class)->record(
            [['variant_id' => $variant->id, 'quantity' => 3]],
            PaymentMethod::Cash,
        );

        // A first-class order on the walk-in channel, paid on the spot.
        $this->assertSame('walk_in', $order->channel);
        $this->assertSame('paid', $order->payment_status->value);
        $this->assertSame(PaymentMethod::Cash, $order->payment_method);

        // Stock deducted through the ledger, stamped with this order.
        $this->assertSame(7, $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'sale',
            'quantity' => -3,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);

        // Tender recorded as a settled payment.
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'provider' => 'cash', 'status' => 'success']);
    }

    public function test_a_walk_in_sale_refuses_to_oversell_and_rolls_back(): void
    {
        $variant = Product::factory()->stock(2)->create()->primaryVariant();

        try {
            app(WalkInSaleService::class)->record([['variant_id' => $variant->id, 'quantity' => 5]], PaymentMethod::Cash);
            $this->fail('Expected an oversell to be refused.');
        } catch (InsufficientStock) {
            // expected
        }

        $this->assertSame(2, $variant->fresh()->stock, 'stock untouched');
        $this->assertDatabaseCount('orders', 0); // the whole sale rolled back
    }

    public function test_the_livewire_screen_completes_a_sale(): void
    {
        $variant = Product::factory()->stock(5)->priced(500)->create()->primaryVariant();

        Livewire::test(WalkInSale::class)
            ->set('lines', [$variant->id => 2])
            ->set('paymentMethod', 'cash')
            ->call('complete')
            ->assertSet('completed.method', 'Cash');

        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertDatabaseHas('orders', ['channel' => 'walk_in', 'payment_status' => 'paid']);
    }

    public function test_in_store_methods_exclude_the_online_gateway_and_pay_on_delivery(): void
    {
        $methods = PaymentMethod::inStore();

        $this->assertContains(PaymentMethod::Cash, $methods);
        $this->assertContains(PaymentMethod::PosTerminal, $methods);
        $this->assertNotContains(PaymentMethod::Paystack, $methods);
        $this->assertNotContains(PaymentMethod::PayOnDelivery, $methods);
    }
}
