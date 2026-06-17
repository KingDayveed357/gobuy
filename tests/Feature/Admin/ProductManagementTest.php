<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_admin_can_view_product_list(): void
    {
        Product::factory()->create(['name' => 'Listed Product']);

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Listed Product');
    }

    public function test_admin_can_create_a_product(): void
    {
        $category = Category::factory()->create();

        $response = $this->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'New Gadget',
            'sku' => 'SKU-123',
            'retail_price' => 5000,
            'wholesale_price' => 4000,
            'wholesale_min_qty' => 10,
            'stock' => 25,
            'status' => 'active',
            'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-123',
            'slug' => 'new-gadget',
            'name' => 'New Gadget',
        ]);
    }

    public function test_creating_a_product_requires_valid_data(): void
    {
        $this->post(route('admin.products.store'), [])
            ->assertSessionHasErrors(['category_id', 'name', 'sku', 'retail_price']);
    }

    public function test_admin_can_update_a_product(): void
    {
        $product = Product::factory()->create();

        $this->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name' => 'Renamed Product',
            'sku' => $product->sku,
            'retail_price' => 9999,
            'wholesale_min_qty' => 1,
            'stock' => 5,
            'status' => 'active',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Renamed Product',
            'retail_price' => 9999,
        ]);
    }

    public function test_admin_can_soft_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted($product);
    }
}
