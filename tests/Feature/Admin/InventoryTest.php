<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_inventory_index_renders_with_stock_columns(): void
    {
        Product::factory()->stock(3)->create();

        $this->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('On hand')
            ->assertSee('Available');
    }

    public function test_admin_can_adjust_stock_and_it_is_audited(): void
    {
        $product = Product::factory()->stock(10)->create();
        $variant = $product->primaryVariant();

        $this->post(route('admin.inventory.adjust', $variant), [
            'mode' => 'adjust',
            'amount' => 5,
            'reason' => 'Canton Fair restock',
        ])->assertRedirect();

        $this->assertSame(15, $variant->fresh()->stock);
        // Adjustments are now audited on the inventory-movement ledger.
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'adjustment',
            'quantity' => 5,
            'quantity_after' => 15,
            'note' => 'Canton Fair restock',
        ]);
    }

    public function test_set_mode_sets_absolute_stock(): void
    {
        $product = Product::factory()->stock(10)->create();
        $variant = $product->primaryVariant();

        $this->post(route('admin.inventory.adjust', $variant), ['mode' => 'set', 'amount' => 4]);

        $this->assertSame(4, $variant->fresh()->stock);
    }

    public function test_csv_import_preview_then_commit(): void
    {
        Category::factory()->create(['name' => 'Safety', 'slug' => 'safety']);
        $existing = Product::factory()->stock(2)->create();
        $existingSku = $existing->primaryVariant()->sku;

        $csv = "sku,name,category,retail_price,stock,status\n"
            ."NEW-IMP-1,Imported Helmet,Safety,15000,30,active\n"
            ."{$existingSku},,,,7,\n"
            .",No SKU Row,,1000,5,active\n";

        $file = UploadedFile::fake()->createWithContent('stock.csv', $csv);

        // Preview classifies rows without committing.
        $preview = $this->post(route('admin.inventory.import.preview'), ['file' => $file])
            ->assertOk()
            ->assertSee('to create')
            ->assertSee('with errors');

        $this->assertDatabaseMissing('product_variants', ['sku' => 'NEW-IMP-1']);

        // Commit imports the valid rows, using the exact token from the preview.
        $token = $preview->viewData('token');

        $this->post(route('admin.inventory.import.store'), ['token' => $token])
            ->assertRedirect(route('admin.inventory.index'));

        $this->assertDatabaseHas('product_variants', ['sku' => 'NEW-IMP-1', 'retail_price' => 1500000]); // kobo
        $this->assertSame(7, ProductVariant::firstWhere('sku', $existingSku)->stock);
    }

    public function test_reservation_reduces_availability_without_touching_stock(): void
    {
        $product = Product::factory()->stock(10)->create();
        $variant = $product->primaryVariant();
        $inventory = app(InventoryService::class);

        $held = $inventory->reserve($variant, 4, 'cart:test');

        $this->assertSame(4, $held);
        $this->assertSame(10, $variant->fresh()->stock); // on-hand unchanged
        $this->assertSame(6, $inventory->availableStock($variant)); // available reduced
    }
}
