<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'condition' => 'new',
            'sku' => 'SKU-123',
            'retail_price' => 5000,
            'wholesale_price' => 4000,
            'stock' => 25,
            'status' => 'active',
            'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'slug' => 'new-gadget',
            'name' => 'New Gadget',
        ]);
        // Prices are stored as integer kobo (Naira × 100).
        $this->assertDatabaseHas('product_variants', [
            'sku' => 'SKU-123',
            'retail_price' => 500000,
            'wholesale_price' => 400000,
            'stock' => 25,
            'is_default' => true,
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
        $variant = $product->primaryVariant();

        $this->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name' => 'Renamed Product',
            'condition' => 'new',
            'sku' => $variant->sku,
            'retail_price' => 9999,
            'stock' => 5,
            'status' => 'active',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Renamed Product',
        ]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'retail_price' => 999900, // kobo
            'stock' => 5,
        ]);
    }

    public function test_create_and_edit_pages_render(): void
    {
        $product = Product::factory()->create();

        $this->get(route('admin.products.create'))->assertOk()->assertSee('Add a product');
        $this->get(route('admin.products.edit', $product))->assertOk()->assertSee('Edit product');
    }

    public function test_admin_can_create_product_with_variants_tiers_and_images(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $response = $this->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Power Bank',
            'condition' => 'new',
            'sku' => 'PB-DEFAULT',
            'retail_price' => 25000,
            'wholesale_price' => 22000,
            'stock' => 100,
            'status' => 'active',
            'variants' => [
                ['name' => '20000mAh', 'sku' => 'PB-20K', 'retail_price' => 30000, 'wholesale_price' => 27000, 'stock' => 40],
            ],
            'quantity_discounts' => [
                ['min_qty' => 10, 'unit_price' => 21000],
                ['min_qty' => 50, 'unit_price' => 20000],
            ],
            'images' => [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('back.jpg'),
            ],
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::firstWhere('name', 'Power Bank');
        $this->assertNotNull($product);
        $this->assertSame(2, $product->variants()->count()); // default + 1 extra
        $this->assertDatabaseHas('product_variants', ['sku' => 'PB-20K', 'is_default' => false, 'stock' => 40]);
        $this->assertSame(2, $product->quantityDiscounts()->count());
        $this->assertSame(2, $product->getMedia(Product::MEDIA_COLLECTION)->count());
    }

    public function test_duplicate_sku_across_variants_is_rejected(): void
    {
        $category = Category::factory()->create();

        $this->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Dup SKU',
            'condition' => 'new',
            'sku' => 'SAME-SKU',
            'retail_price' => 1000,
            'stock' => 1,
            'status' => 'active',
            'variants' => [
                ['name' => 'Clash', 'sku' => 'SAME-SKU', 'retail_price' => 1000, 'stock' => 1],
            ],
        ])->assertSessionHasErrors('variants.0.sku');
    }

    public function test_admin_can_edit_variants_and_remove_one(): void
    {
        $product = Product::factory()->create();
        $extra = $product->variants()->create([
            'name' => 'Old Variant', 'sku' => 'OLD-VAR', 'retail_price' => 5000, 'stock' => 3, 'is_default' => false,
        ]);
        $variant = $product->primaryVariant();

        // Submit with no variants[] -> the extra variant should be removed.
        $this->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'condition' => 'new',
            'sku' => $variant->sku,
            'retail_price' => 9999,
            'stock' => 5,
            'status' => 'active',
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('product_variants', ['id' => $extra->id]);
        $this->assertSame(1, $product->variants()->count());
    }

    public function test_admin_can_soft_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $this->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted($product);
    }
}
