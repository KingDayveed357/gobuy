<?php

namespace Tests\Feature\Operations;

use App\Models\Setting;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\InventoryLedger;
use App\Modules\Operations\Dashboards\Services\OperationsReport;
use App\Modules\Operations\WalkIn\Services\WalkInSaleService;
use App\Modules\Order\Enums\PaymentMethod;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class OperationsDashboardTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
        Setting::put('modules.ops.dashboards', '1');
    }

    private function report(): OperationsReport
    {
        return app(OperationsReport::class);
    }

    public function test_the_dashboard_is_reachable_when_the_module_is_on(): void
    {
        $this->get(route('admin.ops-dashboard.index'))->assertOk();
    }

    public function test_the_dashboard_404s_when_the_module_is_off(): void
    {
        Setting::put('modules.ops.dashboards', '0');

        $this->get(route('admin.ops-dashboard.index'))->assertNotFound();
    }

    public function test_inventory_totals_reflect_on_hand_stock_and_value(): void
    {
        $variant = Product::factory()->stock(0)->priced(500)->create()->primaryVariant(); // ₦500
        app(InventoryLedger::class)->recordAdjustment($variant, 100); // materialises the default level at 100

        $totals = $this->report()->inventoryTotals();

        $this->assertSame(100, $totals['units']);
        $this->assertSame(1, $totals['skus']);
        $this->assertTrue($totals['value']->equals(Money::fromNaira(50000))); // 100 × ₦500

        $byLocation = $this->report()->inventoryByLocation();
        $this->assertSame(100, $byLocation->first()['units']);
    }

    public function test_a_walk_in_sale_shows_up_in_sales_by_channel_and_top_movers(): void
    {
        $variant = Product::factory()->stock(20)->priced(50000)->create()->primaryVariant();
        app(WalkInSaleService::class)->record([['variant_id' => $variant->id, 'quantity' => 3]], PaymentMethod::Cash);

        $channels = $this->report()->salesByChannel();
        $walkIn = $channels->firstWhere('channel', 'walk_in');
        $this->assertNotNull($walkIn);
        $this->assertSame(1, $walkIn['orders']);
        $this->assertTrue($walkIn['revenue']->isPositive());

        $movers = $this->report()->topMovers();
        $this->assertSame(3, $movers->firstWhere('sku', $variant->sku)['units']);
    }

    public function test_low_stock_lists_variants_at_or_below_the_threshold(): void
    {
        $low = Product::factory()->stock(2)->create()->primaryVariant();
        Product::factory()->stock(50)->create();

        $lowStock = $this->report()->lowStock(5);

        $this->assertTrue($lowStock->contains(fn ($v): bool => $v->id === $low->id));
        $this->assertFalse($lowStock->contains(fn ($v): bool => $v->stock > 5));
    }
}
