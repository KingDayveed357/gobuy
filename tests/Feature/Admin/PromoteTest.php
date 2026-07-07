<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class PromoteTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_the_products_list_exposes_a_promote_control_and_shared_modal(): void
    {
        $this->actingAsAdmin('Super Admin');
        $product = Product::factory()->stock(5)->create(['name' => 'Promo Product']);

        $this->get(route('admin.products.index'))->assertOk()
            ->assertSee('js-promote', false)
            ->assertSee('data-promote-name="Promo Product"', false)
            ->assertSee(route('products.show', $product), false) // the shareable storefront URL
            ->assertSee('id="promoteModal"', false);             // shared modal is present
    }
}
