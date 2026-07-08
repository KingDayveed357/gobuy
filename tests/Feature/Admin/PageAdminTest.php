<?php

namespace Tests\Feature\Admin;

use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Models\Page;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class PageAdminTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_admin_can_create_a_landing_page_with_an_auto_slug(): void
    {
        $this->post(route('admin.pages.store'), ['title' => 'Summer Sale', 'status' => 'published'])->assertRedirect();

        $page = Page::firstWhere('title', 'Summer Sale');
        $this->assertNotNull($page);
        $this->assertSame('summer-sale', $page->slug);
    }

    public function test_the_reserved_home_slug_is_rejected(): void
    {
        $this->post(route('admin.pages.store'), ['title' => 'Fake Home', 'slug' => 'home'])
            ->assertSessionHasErrors('slug');
    }

    public function test_the_home_page_cannot_be_deleted(): void
    {
        $home = Page::where('slug', 'home')->first();

        $this->delete(route('admin.pages.destroy', $home))->assertForbidden();
        $this->assertNotNull($home->fresh());
    }

    public function test_deleting_a_page_removes_its_sections(): void
    {
        $page = Page::create(['title' => 'Temp', 'slug' => 'temp', 'status' => 'published']);
        HomepageSection::create(['placement' => 'temp', 'type' => 'product_grid', 'source' => 'latest', 'title' => 'X', 'is_active' => true]);

        $this->delete(route('admin.pages.destroy', $page))->assertRedirect();

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        $this->assertDatabaseMissing('homepage_sections', ['placement' => 'temp']);
    }

    public function test_sections_created_in_the_builder_belong_to_the_selected_page(): void
    {
        Page::create(['title' => 'Promo Page', 'slug' => 'promo-page', 'status' => 'published']);
        \App\Modules\Catalog\Models\Product::factory()->stock(5)->create(); // so the rail resolves & can publish

        $this->post(route('admin.merchandising.store'), [
            'type' => 'product_grid', 'source' => 'latest', 'title' => 'Promo Rail',
            'placement' => 'promo-page', 'is_active' => 1, 'status' => 'published',
        ])->assertRedirect();

        $this->assertSame('promo-page', HomepageSection::firstWhere('title', 'Promo Rail')->placement);
    }

    public function test_the_builder_can_be_scoped_to_a_page(): void
    {
        Page::create(['title' => 'Scoped Page', 'slug' => 'scoped-page', 'status' => 'published']);

        $this->get(route('admin.merchandising.index', ['page' => 'scoped-page']))
            ->assertOk()
            ->assertSee('Scoped Page');
    }
}
