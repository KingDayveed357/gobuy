<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_admin_can_view_categories(): void
    {
        $this->actingAsAdmin('Manager');
        Category::factory()->create(['name' => 'Electronics']);

        $this->get(route('admin.categories.index'))->assertOk()->assertSee('Electronics');
    }

    public function test_admin_can_create_a_root_category(): void
    {
        $this->actingAsAdmin('Manager');

        $this->post(route('admin.categories.store'), ['name' => 'Groceries', 'is_active' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Groceries', 'slug' => 'groceries', 'parent_id' => null]);
    }

    public function test_admin_can_create_a_subcategory(): void
    {
        $this->actingAsAdmin('Manager');
        $parent = Category::factory()->create(['name' => 'Electronics']);

        $this->post(route('admin.categories.store'), ['name' => 'Laptops', 'parent_id' => $parent->id, 'is_active' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Laptops', 'parent_id' => $parent->id]);
    }

    public function test_inline_json_create_returns_the_new_category(): void
    {
        $this->actingAsAdmin('Manager');

        $this->postJson(route('admin.categories.store'), ['name' => 'Phones', 'is_active' => 1])
            ->assertCreated()
            ->assertJson(['name' => 'Phones', 'parent_id' => null]);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $this->actingAsAdmin('Manager');
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->delete(route('admin.categories.destroy', $category))->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_with_children_cannot_be_deleted(): void
    {
        $this->actingAsAdmin('Manager');
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->delete(route('admin.categories.destroy', $parent))->assertSessionHas('error');
    }

    public function test_support_cannot_manage_categories(): void
    {
        $this->actingAsAdmin('Support'); // lacks manage_products

        $this->get(route('admin.categories.index'))->assertForbidden();
        $this->post(route('admin.categories.store'), ['name' => 'X'])->assertForbidden();
    }
}
