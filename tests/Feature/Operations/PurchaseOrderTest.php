<?php

namespace Tests\Feature\Operations;

use App\Admin\Models\Admin;
use App\Livewire\Admin\Purchasing\PurchaseOrderBuilder;
use App\Livewire\Admin\Purchasing\ReceiveGoods;
use App\Models\Setting;
use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Operations\Purchasing\Enums\PurchaseOrderStatus;
use App\Modules\Operations\Purchasing\Exceptions\PurchasingException;
use App\Modules\Operations\Purchasing\Models\PurchaseOrder;
use App\Modules\Operations\Purchasing\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->actingAsAdmin('Super Admin');
        Setting::put('modules.ops.inventory_ledger', '1');
        Setting::put('modules.ops.purchasing', '1'); // depends on ops.inventory_ledger
    }

    private function service(): PurchaseOrderService
    {
        return app(PurchaseOrderService::class);
    }

    public function test_the_purchasing_screens_are_reachable_when_the_module_is_on(): void
    {
        $this->get(route('admin.purchase-orders.index'))->assertOk();
        $this->get(route('admin.suppliers.index'))->assertOk();
    }

    public function test_the_purchasing_screens_404_when_the_module_is_off(): void
    {
        Setting::put('modules.ops.purchasing', '0');

        $this->get(route('admin.purchase-orders.index'))->assertNotFound();
        $this->get(route('admin.suppliers.index'))->assertNotFound();
    }

    public function test_a_supplier_can_be_created(): void
    {
        $this->post(route('admin.suppliers.store'), ['name' => 'Lagos Beverages', 'phone' => '08012345678'])
            ->assertRedirect();

        $this->assertDatabaseHas('suppliers', ['name' => 'Lagos Beverages', 'phone' => '08012345678', 'is_active' => true]);
    }

    public function test_creating_a_purchase_order_does_not_touch_stock(): void
    {
        $variant = Product::factory()->stock(5)->create()->primaryVariant();

        $po = $this->service()->create(
            InventoryLocation::default(),
            [['variant_id' => $variant->id, 'quantity' => 10, 'unit_cost' => 250000]],
            ['admin' => $this->admin],
        );

        $this->assertSame(PurchaseOrderStatus::Draft, $po->status);
        $this->assertStringStartsWith('PO-', $po->reference);
        $this->assertSame(5, $variant->fresh()->stock, 'raising a PO must not change stock');
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $po->id,
            'product_variant_id' => $variant->id,
            'quantity_ordered' => 10,
            'quantity_received' => 0,
            'unit_cost' => 250000,
        ]);
    }

    public function test_placing_an_order_advances_it_to_ordered(): void
    {
        $variant = Product::factory()->stock(0)->create()->primaryVariant();
        $po = $this->service()->create(InventoryLocation::default(), [['variant_id' => $variant->id, 'quantity' => 4]]);

        $po = $this->service()->place($po);

        $this->assertSame(PurchaseOrderStatus::Ordered, $po->status);
        $this->assertNotNull($po->ordered_at);
    }

    public function test_receiving_goods_lands_stock_through_the_ledger_and_completes_the_order(): void
    {
        $variant = Product::factory()->stock(2)->create()->primaryVariant();
        $location = InventoryLocation::default();
        $po = $this->service()->create($location, [['variant_id' => $variant->id, 'quantity' => 8]], ['place' => true]);
        $item = $po->items->first();

        $po = $this->service()->receive($po, [$item->id => 8], $this->admin);

        // Stock went up by the received quantity, recorded as a receipt movement.
        $this->assertSame(10, $variant->fresh()->stock);
        $this->assertSame(10, (int) StockLevel::query()->where('inventory_location_id', $location->id)->where('product_variant_id', $variant->id)->value('on_hand'));
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'inventory_location_id' => $location->id,
            'type' => 'receipt',
            'quantity' => 8,
            'reference_type' => PurchaseOrder::class,
            'reference_id' => $po->id,
        ]);

        // The order is fully received.
        $this->assertSame(PurchaseOrderStatus::Received, $po->status);
        $this->assertSame(8, $po->items->first()->quantity_received);
        $this->assertNotNull($po->received_at);
    }

    public function test_a_partial_receipt_leaves_the_order_partially_received(): void
    {
        $variant = Product::factory()->stock(0)->create()->primaryVariant();
        $po = $this->service()->create(InventoryLocation::default(), [['variant_id' => $variant->id, 'quantity' => 10]], ['place' => true]);
        $item = $po->items->first();

        $po = $this->service()->receive($po, [$item->id => 6], $this->admin);

        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $po->status);
        $this->assertSame(6, $po->items->first()->quantity_received);
        $this->assertSame(6, $variant->fresh()->stock);
        $this->assertNull($po->received_at);

        // Receiving the rest completes it.
        $po = $this->service()->receive($po, [$item->id => 4], $this->admin);
        $this->assertSame(PurchaseOrderStatus::Received, $po->status);
        $this->assertSame(10, $variant->fresh()->stock);
    }

    public function test_receiving_more_than_outstanding_is_refused_and_rolls_back(): void
    {
        $variant = Product::factory()->stock(0)->create()->primaryVariant();
        $po = $this->service()->create(InventoryLocation::default(), [['variant_id' => $variant->id, 'quantity' => 5]], ['place' => true]);
        $item = $po->items->first();

        try {
            $this->service()->receive($po, [$item->id => 9], $this->admin);
            $this->fail('Expected an over-receipt to be refused.');
        } catch (PurchasingException) {
            // expected
        }

        $this->assertSame(0, $variant->fresh()->stock, 'stock untouched');
        $this->assertSame(0, $po->items->first()->fresh()->quantity_received);
        $this->assertSame(PurchaseOrderStatus::Ordered, $po->fresh()->status);
    }

    public function test_a_received_order_cannot_be_cancelled(): void
    {
        $variant = Product::factory()->stock(0)->create()->primaryVariant();
        $po = $this->service()->create(InventoryLocation::default(), [['variant_id' => $variant->id, 'quantity' => 3]], ['place' => true]);
        $this->service()->receive($po, [$po->items->first()->id => 3], $this->admin);

        $this->expectException(PurchasingException::class);
        $this->service()->cancel($po->fresh());
    }

    public function test_the_livewire_builder_places_an_order(): void
    {
        $variant = Product::factory()->stock(0)->create()->primaryVariant();
        $location = InventoryLocation::default();

        Livewire::test(PurchaseOrderBuilder::class)
            ->set('locationId', $location->id)
            ->set('lines', [$variant->id => ['quantity' => 12, 'unit_cost' => '1500']])
            ->call('save', true)
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_orders', ['inventory_location_id' => $location->id, 'status' => 'ordered']);
        $this->assertDatabaseHas('purchase_order_items', ['product_variant_id' => $variant->id, 'quantity_ordered' => 12, 'unit_cost' => 150000]);
    }

    public function test_the_livewire_receive_screen_lands_stock(): void
    {
        $variant = Product::factory()->stock(1)->create()->primaryVariant();
        $po = $this->service()->create(InventoryLocation::default(), [['variant_id' => $variant->id, 'quantity' => 5]], ['place' => true]);

        Livewire::test(ReceiveGoods::class, ['order' => $po])
            ->call('submit')
            ->assertRedirect();

        $this->assertSame(6, $variant->fresh()->stock);
        $this->assertSame(PurchaseOrderStatus::Received, $po->fresh()->status);
    }
}
