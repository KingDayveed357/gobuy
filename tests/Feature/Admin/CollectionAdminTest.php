<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\ProductCollection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class CollectionAdminTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_admin_can_create_a_collection_and_add_a_product(): void
    {
        $product = Product::factory()->stock(5)->create();

        $this->post(route('admin.collections.store'), ['name' => 'Holiday Picks', 'is_active' => 1])->assertRedirect();
        $collection = ProductCollection::firstWhere('name', 'Holiday Picks');
        $this->assertNotNull($collection);
        $this->assertNotEmpty($collection->slug);

        $this->post(route('admin.collections.attach', $collection), ['product_id' => $product->id])->assertRedirect();
        $this->assertDatabaseHas('collection_products', [
            'collection_id' => $collection->id, 'product_id' => $product->id,
        ]);
    }

    public function test_admin_can_reorder_and_detach_products(): void
    {
        $collection = ProductCollection::create(['name' => 'Deals', 'is_active' => true]);
        $a = Product::factory()->stock(5)->create();
        $b = Product::factory()->stock(5)->create();
        $collection->products()->attach($a->id, ['sort_order' => 0]);
        $collection->products()->attach($b->id, ['sort_order' => 1]);

        // Reorder: b first, then a.
        $this->post(route('admin.collections.reorder', $collection), ['product_ids' => [$b->id, $a->id]])->assertRedirect();
        $this->assertSame(0, (int) $collection->products()->where('product_id', $b->id)->first()->pivot->sort_order);

        // Detach a.
        $this->delete(route('admin.collections.detach', [$collection, $a]))->assertRedirect();
        $this->assertDatabaseMissing('collection_products', ['collection_id' => $collection->id, 'product_id' => $a->id]);
    }
}
