<?php

namespace Tests\Feature\Marketing;

use App\Modules\Catalog\Models\Product;
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

    public function test_the_home_slug_is_not_served_as_a_landing_page(): void
    {
        $this->get('/p/home')->assertNotFound();
    }

    public function test_an_unknown_page_returns_404(): void
    {
        $this->get('/p/does-not-exist')->assertNotFound();
    }
}
