<?php

namespace Tests\Feature\Inventory;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Inventory\Services\InventoryLedger;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InventoryLedgerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function ledger(): InventoryLedger
    {
        return app(InventoryLedger::class);
    }

    private function variant(int $stock): ProductVariant
    {
        return Product::factory()->stock($stock)->create()->primaryVariant();
    }

    public function test_a_sale_records_a_movement_and_lowers_stock_and_level(): void
    {
        $variant = $this->variant(10);

        $this->ledger()->recordSale($variant, 3);

        $this->assertSame(7, $variant->fresh()->stock, 'read-model updated');
        $this->assertSame(7, $this->ledger()->totalOnHand($variant), 'ledger level matches');
        $this->assertDatabaseHas('inventory_movements', ['product_variant_id' => $variant->id, 'type' => 'sale', 'quantity' => -3, 'quantity_after' => 7]);
        // The first touch adopts current stock as an opening balance.
        $this->assertDatabaseHas('inventory_movements', ['product_variant_id' => $variant->id, 'type' => 'opening', 'quantity' => 10]);
    }

    public function test_overselling_is_refused(): void
    {
        $variant = $this->variant(2);

        $this->expectException(InsufficientStock::class);
        $this->ledger()->recordSale($variant, 5);
    }

    public function test_a_return_puts_stock_back(): void
    {
        $variant = $this->variant(0);

        $this->ledger()->recordReturn($variant, 4);

        $this->assertSame(4, $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', ['product_variant_id' => $variant->id, 'type' => 'return', 'quantity' => 4, 'quantity_after' => 4]);
    }

    public function test_a_manual_adjustment_records_a_movement_and_no_longer_writes_the_legacy_log(): void
    {
        $variant = $this->variant(5);

        app(InventoryService::class)->adjust($variant, 3, 'Received delivery');

        $this->assertSame(8, $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', ['type' => 'adjustment', 'quantity' => 3, 'note' => 'Received delivery']);
        $this->assertDatabaseCount('stock_adjustments', 0); // the ledger is the one audit trail now
    }

    public function test_an_adjustment_is_clamped_at_zero(): void
    {
        $variant = $this->variant(3);

        app(InventoryService::class)->adjust($variant, -10);

        $this->assertSame(0, $variant->fresh()->stock);
    }

    public function test_reconcile_heals_an_out_of_band_stock_edit(): void
    {
        $variant = $this->variant(10);
        $this->ledger()->recordSale($variant, 1); // ledger now knows this variant (level 9)

        // Simulate the product form writing stock directly, bypassing the ledger.
        ProductVariant::query()->whereKey($variant->id)->update(['stock' => 20]);
        $this->assertSame(9, $this->ledger()->totalOnHand($variant), 'ledger lags the out-of-band edit');

        $this->ledger()->reconcile($variant->fresh());

        $this->assertSame(20, $this->ledger()->totalOnHand($variant), 'ledger healed to the read-model');
        $this->assertDatabaseHas('inventory_movements', ['type' => 'adjustment', 'note' => 'Reconciliation', 'quantity' => 11]);
    }

    public function test_the_reconcile_command_reports_then_heals_with_fix(): void
    {
        $variant = $this->variant(10);
        $this->ledger()->recordSale($variant, 1);
        ProductVariant::query()->whereKey($variant->id)->update(['stock' => 15]);

        $this->artisan('inventory:reconcile')->assertSuccessful();
        $this->assertSame(9, $this->ledger()->totalOnHand($variant->fresh()), 'report-only leaves it drifted');

        $this->artisan('inventory:reconcile', ['--fix' => true])->assertSuccessful();
        $this->assertSame(15, $this->ledger()->totalOnHand($variant->fresh()), 'fix aligns the ledger');
    }
}
