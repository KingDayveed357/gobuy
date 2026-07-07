<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\HomepageSection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class LinkPickerTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_search_returns_matching_products_by_name(): void
    {
        Product::factory()->create(['name' => 'Blue Sneaker']);
        Product::factory()->create(['name' => 'Red Hat']);

        $this->getJson(route('admin.link-picker.search', ['type' => 'product', 'q' => 'sneak']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Blue Sneaker'])
            ->assertJsonMissing(['label' => 'Red Hat']);
    }

    public function test_a_banner_saves_and_resolves_a_structured_link(): void
    {
        $product = Product::factory()->stock(5)->create(['name' => 'Linked Product']);

        $this->post(route('admin.banners.store'), [
            'title' => 'Promo', 'placement' => 'home_hero', 'layout' => 'hero',
            'theme' => 'indigo', 'text_theme' => 'light', 'is_active' => 1,
            'link' => ['type' => 'product', 'ref' => (string) $product->id, 'label' => 'Linked Product'],
        ])->assertRedirect();

        $banner = Banner::firstWhere('title', 'Promo');
        $this->assertSame('product', $banner->cta_link['type']);
        $this->assertSame(route('products.show', $product), $banner->destinationUrl());
    }

    public function test_the_activate_toggle_does_not_wipe_the_link(): void
    {
        $product = Product::factory()->create();
        $banner = Banner::create([
            'title' => 'B', 'placement' => 'home_hero', 'is_active' => true,
            'cta_link' => ['type' => 'product', 'ref' => (string) $product->id, 'label' => 'X'],
        ]);

        // The list toggle submits no `link` field — the link must survive.
        $this->put(route('admin.banners.update', $banner), [
            'title' => 'B', 'placement' => 'home_hero', 'layout' => 'hero',
            'theme' => 'indigo', 'text_theme' => 'light', 'is_active' => 0,
        ])->assertRedirect();

        $this->assertNotNull($banner->fresh()->cta_link);
    }

    public function test_a_section_saves_a_structured_link(): void
    {
        $category = Category::factory()->create();

        $this->post(route('admin.merchandising.store'), [
            'type' => 'product_grid', 'source' => 'latest', 'title' => 'Grid', 'is_active' => 1,
            'link' => ['type' => 'category', 'ref' => (string) $category->id, 'label' => $category->name],
        ])->assertRedirect();

        $this->assertSame('category', HomepageSection::firstWhere('title', 'Grid')->cta_link['type']);
    }

    public function test_the_audit_command_flags_broken_links(): void
    {
        Banner::create([
            'title' => 'Broken', 'placement' => 'home_hero', 'is_active' => true,
            'cta_link' => ['type' => 'product', 'ref' => '999999'],
        ]);

        $this->artisan('merchandising:audit-links')
            ->expectsOutputToContain('Broken')
            ->assertExitCode(0);
    }
}
