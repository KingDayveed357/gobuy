<?php

namespace Tests\Feature\Marketing;

use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\Campaign;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Models\Page;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_published_landing_page_renders_its_sections(): void
    {
        Product::factory()->stock(5)->create(['name' => 'BF Deal']);
        Page::create(['title' => 'Black Friday', 'slug' => 'black-friday', 'status' => 'published']);
        HomepageSection::create([
            'placement' => 'black-friday', 'type' => 'product_grid', 'source' => 'latest',
            'title' => 'BF Rail', 'is_active' => true, 'status' => 'published',
        ]);

        $this->get('/p/black-friday')->assertOk()
            ->assertSee('Black Friday')   // page title
            ->assertSee('BF Rail')        // section
            ->assertSee('BF Deal');       // product
    }

    public function test_a_draft_page_is_not_public_but_is_previewable(): void
    {
        Product::factory()->stock(5)->create(['name' => 'Sneak Product']);
        Page::create(['title' => 'Sneak', 'slug' => 'sneak', 'status' => 'draft']);
        HomepageSection::create([
            'placement' => 'sneak', 'type' => 'product_grid', 'source' => 'latest',
            'title' => 'Sneak Rail', 'is_active' => true, 'status' => 'published',
        ]);

        $this->get('/p/sneak')->assertNotFound();

        $preview = URL::temporarySignedRoute('storefront.preview', now()->addHour(), ['slug' => 'sneak']);
        $this->get($preview)->assertOk()->assertSee('Sneak Rail')->assertSee('Preview mode', false);
    }

    public function test_a_landing_page_has_seo_meta_and_breadcrumbs(): void
    {
        Page::create(['title' => 'Summer Sale', 'slug' => 'summer-sale', 'status' => 'published', 'meta_description' => 'Hot summer deals']);

        $this->get('/p/summer-sale')->assertOk()
            ->assertSee('<meta property="og:title" content="Summer Sale">', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('Hot summer deals')                 // meta description + hero lead
            ->assertSee('"@type":"BreadcrumbList"', false)  // structured data
            ->assertSee('aria-label="Breadcrumb"', false);  // visible breadcrumb
    }

    public function test_a_campaign_page_shows_a_branded_hero_and_a_creative_og_image(): void
    {
        $page = Page::create(['title' => 'Black Friday', 'slug' => 'black-friday', 'status' => 'published']);
        Campaign::create(['name' => 'Black Friday', 'page_id' => $page->id, 'badge_text' => 'FLASH SALE', 'accent_color' => '#e63757']);

        $banner = Banner::create(['title' => 'BF Hero', 'placement' => 'home_hero', 'is_active' => true]);
        $banner->addMedia(public_path('theme/img/products/3.png'))->preservingOriginal()->toMediaCollection(Banner::MEDIA_IMAGE);
        HomepageSection::create([
            'placement' => 'black-friday', 'type' => 'banner_row', 'is_active' => true, 'status' => 'published',
            'settings' => ['banner_ids' => [$banner->id]],
        ]);

        $response = $this->get('/p/black-friday')->assertOk();
        $response->assertSee('gb-page-hero--branded', false)  // branded hero band
            ->assertSee('FLASH SALE')                          // campaign badge
            ->assertSee('<meta property="og:image"', false)   // derived from the banner creative
            ->assertSee('twitter:card" content="summary_large_image"', false);
    }

    public function test_the_home_slug_is_not_served_as_a_landing_page(): void
    {
        $this->get('/p/home')->assertNotFound();
    }

    public function test_an_unknown_page_returns_404(): void
    {
        $this->get('/p/does-not-exist')->assertNotFound();
    }
}
