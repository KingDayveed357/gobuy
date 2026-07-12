<?php

namespace Tests\Feature\Operations;

use App\Admin\Models\Admin;
use App\Livewire\Admin\StockCounts\RecordStockCount;
use App\Livewire\Admin\StockCounts\WriteOffDamage;
use App\Models\Setting;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Operations\StockCounts\Models\StockCount;
use App\Modules\Operations\StockCounts\Services\StockCountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class StockCountTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->actingAsAdmin('Super Admin');
        Setting::put('modules.ops.inventory_ledger', '1');
        Setting::put('modules.ops.stock_counts', '1'); // depends on ops.inventory_ledger
    }

    private function service(): StockCountService
    {
        return app(StockCountService::class);
    }

    public function test_the_stock_counts_screen_is_reachable_when_the_module_is_on(): void
    {
        $this->get(route('admin.stock-counts.index'))
            ->assertOk()
            ->assertSeeLivewire(RecordStockCount::class)
            ->assertSeeLivewire(WriteOffDamage::class);
    }

    public function test_the_stock_counts_screen_404s_when_the_module_is_off(): void
    {
        Setting::put('modules.ops.stock_counts', '0');

        $this->get(route('admin.stock-counts.index'))->assertNotFound();
    }

    public function test_a_count_reconciles_stock_up_and_records_the_variance(): void
    {
        $variant = Product::factory()->stock(10)->create()->primaryVariant();
        $location = InventoryLocation::default();

        $count = $this->service()->record($location, [$variant->id => 13], 'Found extra', $this->admin);

        // Books caught up to the counted figure via a `count` movement.
        $this->assertSame(13, $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'inventory_location_id' => $location->id,
            'type' => 'count',
            'quantity' => 3,
            'reference_type' => StockCount::class,
            'reference_id' => $count->id,
        ]);

        // The count line captured expected vs counted (variance +3).
        $item = $count->items->first();
        $this->assertSame(10, $item->expected_qty);
        $this->assertSame(13, $item->counted_qty);
        $this->assertSame(3, $item->variance());
        $this->assertSame(3, $count->netVariance());
    }

    public function test_a_count_reconciles_stock_down_for_shrinkage(): void
    {
        $variant = Product::factory()->stock(20)->create()->primaryVariant();

        $count = $this->service()->record(InventoryLocation::default(), [$variant->id => 17], null, $this->admin);

        $this->assertSame(17, $variant->fresh()->stock);
        $this->assertSame(-3, $count->items->first()->variance());
    }

    public function test_a_count_matching_the_system_records_no_movement(): void
    {
        $variant = Product::factory()->stock(8)->create()->primaryVariant();

        $count = $this->service()->record(InventoryLocation::default(), [$variant->id => 8], null, $this->admin);

        $this->assertSame(8, $variant->fresh()->stock);
        $this->assertDatabaseMissing('inventory_movements', ['type' => 'count', 'product_variant_id' => $variant->id]);
        // The count itself is still recorded (a zero-variance line).
        $this->assertSame(0, $count->netVariance());
    }

    public function test_writing_off_damage_removes_stock_and_logs_a_damage_movement(): void
    {
        $variant = Product::factory()->stock(15)->create()->primaryVariant();
        $location = InventoryLocation::default();

        $this->service()->writeOffDamage($variant, 4, $location, 'Broken bottles', $this->admin);

        $this->assertSame(11, $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'inventory_location_id' => $location->id,
            'type' => 'damage',
            'quantity' => -4,
            'note' => 'Broken bottles',
        ]);
    }

    public function test_writing_off_more_than_on_hand_clamps_at_zero(): void
    {
        $variant = Product::factory()->stock(3)->create()->primaryVariant();

        $this->service()->writeOffDamage($variant, 10, InventoryLocation::default(), null, $this->admin);

        $this->assertSame(0, $variant->fresh()->stock);
    }

    public function test_the_livewire_count_screen_records_a_count(): void
    {
        $variant = Product::factory()->stock(5)->create()->primaryVariant();
        $location = InventoryLocation::default();

        Livewire::test(RecordStockCount::class)
            ->set('locationId', $location->id)
            ->set('counts', [$variant->id => 9])
            ->call('submit')
            ->assertDispatched('toast', type: 'success', message: 'Stock count recorded.');

        $this->assertSame(9, $variant->fresh()->stock);
    }

    public function test_the_livewire_damage_screen_writes_off_stock(): void
    {
        $variant = Product::factory()->stock(6)->create()->primaryVariant();
        $location = InventoryLocation::default();

        Livewire::test(WriteOffDamage::class)
            ->set('locationId', $location->id)
            ->call('choose', $variant->id)
            ->set('quantity', 2)
            ->set('reason', 'Expired')
            ->call('submit')
            ->assertDispatched('toast', type: 'success', message: 'Damage written off.');

        $this->assertSame(4, $variant->fresh()->stock);
    }
}
