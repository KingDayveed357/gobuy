<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Sprint item #3 — the shared admin list table renders a navigation-loading
 * skeleton overlay (revealed by table-loading.js on a filter/pagination reload).
 */
class TableLoadingTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_the_products_list_ships_a_skeleton_overlay_and_its_loader(): void
    {
        Product::factory()->create(['name' => 'Test Beer']);

        $this->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('data-admin-table', false)
            ->assertSee('admin-table-loading', false)
            ->assertSee('gb-skel-table', false)
            ->assertSee('theme/js/table-loading.js', false);
    }

    public function test_the_inventory_list_also_ships_the_overlay(): void
    {
        Product::factory()->stock(3)->create(['name' => 'Low Beer']);

        $this->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSee('data-admin-table', false)
            ->assertSee('admin-table-loading', false)
            ->assertSee('theme/js/table-loading.js', false);
    }
}
