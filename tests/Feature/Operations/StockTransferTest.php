<?php

namespace Tests\Feature\Operations;

use App\Admin\Models\Admin;
use App\Livewire\Admin\Transfers\TransferStock;
use App\Models\Setting;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Operations\Transfers\Services\TransferService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->actingAsAdmin('Super Admin');
        Setting::put('modules.ops.inventory_ledger', '1');
        Setting::put('modules.ops.transfers', '1'); // depends on ops.inventory_ledger
    }

    private function shop(): InventoryLocation
    {
        return InventoryLocation::create(['name' => 'Shop', 'code' => 'shop', 'type' => 'shop', 'is_active' => true]);
    }

    public function test_the_transfers_screen_is_reachable_when_the_module_is_on(): void
    {
        $this->get(route('admin.stock-transfers.index'))
            ->assertOk()
            ->assertSeeLivewire(TransferStock::class);
    }

    public function test_the_transfers_screen_404s_when_the_module_is_off(): void
    {
        Setting::put('modules.ops.transfers', '0');

        $this->get(route('admin.stock-transfers.index'))->assertNotFound();
    }

    public function test_the_locations_screen_404s_when_the_inventory_module_is_off(): void
    {
        Setting::put('modules.ops.inventory_ledger', '0');
        Setting::put('modules.ops.transfers', '0'); // dependent, would otherwise cascade

        $this->get(route('admin.stock-locations.index'))->assertNotFound();
    }

    public function test_a_location_can_be_created(): void
    {
        $this->post(route('admin.stock-locations.store'), ['name' => 'Home Storage', 'type' => 'storage'])
            ->assertRedirect();

        $this->assertDatabaseHas('inventory_locations', ['name' => 'Home Storage', 'type' => 'storage', 'is_active' => true]);
    }

    public function test_a_transfer_moves_stock_between_locations_and_records_history(): void
    {
        $variant = Product::factory()->stock(10)->create()->primaryVariant();
        $home = InventoryLocation::default(); // holds the opening balance
        $shop = $this->shop();

        $transfer = app(TransferService::class)->transfer(
            $home,
            $shop,
            [['variant_id' => $variant->id, 'quantity' => 4]],
            'Restocking the shop',
            $this->admin,
        );

        // The header + line were recorded.
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'from_location_id' => $home->id,
            'to_location_id' => $shop->id,
            'note' => 'Restocking the shop',
            'created_by_id' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('stock_transfer_items', [
            'stock_transfer_id' => $transfer->id,
            'product_variant_id' => $variant->id,
            'quantity' => 4,
        ]);

        // Stock partitioned across the two locations…
        $this->assertSame(6, (int) StockLevel::query()->where('inventory_location_id', $home->id)->where('product_variant_id', $variant->id)->value('on_hand'));
        $this->assertSame(4, (int) StockLevel::query()->where('inventory_location_id', $shop->id)->where('product_variant_id', $variant->id)->value('on_hand'));

        // …but the total (the Core read-model) is unchanged.
        $this->assertSame(10, $variant->fresh()->stock);

        // A transfer_out / transfer_in movement pair in the audit trail.
        $this->assertDatabaseHas('inventory_movements', ['product_variant_id' => $variant->id, 'inventory_location_id' => $home->id, 'type' => 'transfer_out', 'quantity' => -4]);
        $this->assertDatabaseHas('inventory_movements', ['product_variant_id' => $variant->id, 'inventory_location_id' => $shop->id, 'type' => 'transfer_in', 'quantity' => 4]);
    }

    public function test_a_transfer_refuses_to_move_more_than_the_source_holds_and_rolls_back(): void
    {
        $variant = Product::factory()->stock(3)->create()->primaryVariant();
        $home = InventoryLocation::default();
        $shop = $this->shop();

        try {
            app(TransferService::class)->transfer($home, $shop, [['variant_id' => $variant->id, 'quantity' => 5]]);
            $this->fail('Expected an oversell transfer to be refused.');
        } catch (InsufficientStock) {
            // expected
        }

        $this->assertSame(3, $variant->fresh()->stock, 'total untouched');
        $this->assertDatabaseCount('stock_transfers', 0); // the whole transfer rolled back
        $this->assertSame(0, (int) StockLevel::query()->where('inventory_location_id', $shop->id)->count(), 'nothing landed at the shop');
    }

    public function test_the_livewire_screen_transfers_stock(): void
    {
        $variant = Product::factory()->stock(8)->create()->primaryVariant();
        $home = InventoryLocation::default();
        $shop = $this->shop();

        Livewire::test(TransferStock::class)
            ->set('fromId', $home->id)
            ->set('toId', $shop->id)
            ->set('lines', [$variant->id => 3])
            ->call('submit')
            ->assertDispatched('toast', type: 'success', message: 'Stock transferred.');

        $this->assertSame(5, (int) StockLevel::query()->where('inventory_location_id', $home->id)->where('product_variant_id', $variant->id)->value('on_hand'));
        $this->assertSame(3, (int) StockLevel::query()->where('inventory_location_id', $shop->id)->where('product_variant_id', $variant->id)->value('on_hand'));
    }

    public function test_the_livewire_screen_refuses_the_same_source_and_destination(): void
    {
        $home = InventoryLocation::default();

        Livewire::test(TransferStock::class)
            ->set('fromId', $home->id)
            ->set('toId', $home->id)
            ->set('lines', [1 => 2])
            ->call('submit')
            ->assertHasErrors('fromId');
    }
}
