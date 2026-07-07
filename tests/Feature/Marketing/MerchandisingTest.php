<?php

namespace Tests\Feature\Marketing;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Enums\SectionSource;
use App\Modules\Marketing\Enums\SectionType;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Models\ProductCollection;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MerchandisingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_default_homepage_shows_products_when_no_sections_configured(): void
    {
        Product::factory()->featured()->stock(5)->create(['name' => 'Featured Widget']);
        Product::factory()->stock(5)->create(['name' => 'Latest Gadget']);

        $this->get(route('home'))->assertOk()
            ->assertSee('Featured Widget')
            ->assertSee('Latest Gadget')
            ->assertSee('Top deals today')
            ->assertSee('New arrivals');
    }

    public function test_a_configured_section_replaces_the_default_composition(): void
    {
        Product::factory()->stock(5)->create(['name' => 'Curated Item']);

        HomepageSection::create([
            'type' => SectionType::ProductGrid->value,
            'source' => SectionSource::Latest->value,
            'title' => 'Fresh Picks', 'item_limit' => 8, 'is_active' => true,
        ]);

        $this->get(route('home'))->assertOk()
            ->assertSee('Fresh Picks')
            ->assertSee('Curated Item')
            ->assertDontSee('Top deals today'); // built-in default no longer used
    }

    public function test_a_scheduled_section_outside_its_window_is_hidden(): void
    {
        Product::factory()->stock(5)->create(['name' => 'Any Product']);

        HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Live Now', 'is_active' => true]);
        HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Coming Soon', 'is_active' => true, 'starts_at' => now()->addWeek()]);

        $response = $this->get(route('home'))->assertOk();
        $response->assertSee('Live Now');
        $response->assertDontSee('Coming Soon');
    }

    public function test_a_category_rail_shows_only_that_categorys_products(): void
    {
        $catA = Category::factory()->create(['name' => 'Cat A']);
        $catB = Category::factory()->create(['name' => 'Cat B']);
        Product::factory()->stock(5)->create(['name' => 'Alpha Prod', 'category_id' => $catA->id]);
        Product::factory()->stock(5)->create(['name' => 'Beta Prod', 'category_id' => $catB->id]);

        HomepageSection::create([
            'type' => 'product_rail', 'source' => 'category', 'source_ref' => (string) $catA->id,
            'title' => 'Category Rail', 'is_active' => true,
        ]);

        $response = $this->get(route('home'))->assertOk();
        $response->assertSee('Alpha Prod');
        $response->assertDontSee('Beta Prod');
    }

    public function test_a_best_sellers_section_renders_without_error(): void
    {
        Product::factory()->stock(5)->create(['name' => 'Popular Item']);

        HomepageSection::create([
            'type' => 'product_rail', 'source' => 'best_sellers',
            'title' => 'Best sellers', 'item_limit' => 8, 'is_active' => true,
        ]);

        $this->get(route('home'))->assertOk()->assertSee('Best sellers')->assertSee('Popular Item');
    }

    public function test_an_empty_section_is_skipped(): void
    {
        HomepageSection::create([
            'type' => 'product_grid', 'source' => 'brand', 'source_ref' => '999999',
            'title' => 'Empty Brand Rail', 'is_active' => true,
        ]);

        $this->get(route('home'))->assertOk()->assertDontSee('Empty Brand Rail');
    }

    public function test_an_on_sale_section_shows_only_discounted_products(): void
    {
        $onSale = Product::factory()->stock(5)->create(['name' => 'Discounted Deal']);
        $onSale->primaryVariant()->promotionalPrices()->create([
            'price' => Money::fromNaira(3000), 'is_active' => true,
        ]);
        Product::factory()->stock(5)->create(['name' => 'Regular Price Item']);

        HomepageSection::create([
            'type' => 'product_grid', 'source' => 'on_sale', 'title' => 'On Sale Now', 'is_active' => true,
        ]);

        $response = $this->get(route('home'))->assertOk();
        $response->assertSee('Discounted Deal');
        $response->assertDontSee('Regular Price Item');
    }

    public function test_a_manual_section_shows_the_curated_collection_in_order(): void
    {
        $p1 = Product::factory()->stock(5)->create(['name' => 'Curated One']);
        $p2 = Product::factory()->stock(5)->create(['name' => 'Curated Two']);
        Product::factory()->stock(5)->create(['name' => 'Not In Collection']);

        $collection = ProductCollection::create(['name' => 'Editors Picks', 'is_active' => true]);
        $collection->products()->attach($p2->id, ['sort_order' => 0]); // shown first
        $collection->products()->attach($p1->id, ['sort_order' => 1]);

        HomepageSection::create([
            'type' => 'product_grid', 'source' => 'manual', 'source_ref' => (string) $collection->id,
            'title' => 'Our Picks', 'is_active' => true,
        ]);

        $response = $this->get(route('home'))->assertOk();
        $response->assertDontSee('Not In Collection');
        $response->assertSeeInOrder(['Curated Two', 'Curated One']); // pivot sort_order respected
    }

    public function test_the_resolved_homepage_is_cached_and_invalidated_on_change(): void
    {
        config(['gobuy.homepage_cache_ttl' => 300]); // opt this test into caching

        $product = Product::factory()->stock(5)->create(['name' => 'Cached Widget']);
        $this->get(route('home'))->assertOk()->assertSee('Cached Widget'); // primes cache

        // Direct DB write bypasses model events — the cache should still serve the old view.
        DB::table('products')->where('id', $product->id)->update(['name' => 'Renamed Widget']);
        $this->get(route('home'))->assertOk()->assertSee('Cached Widget')->assertDontSee('Renamed Widget');

        // Saving a section flushes the cache → next build reflects the new data.
        HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Fresh', 'is_active' => true]);
        $this->get(route('home'))->assertOk()->assertSee('Renamed Widget');

        HomepageMerchandiser::forget(); // leave the shared cache clean
    }

    public function test_a_draft_section_is_hidden_live_but_shown_in_signed_preview(): void
    {
        Product::factory()->stock(5)->create(['name' => 'Staged Product']);
        HomepageSection::create([
            'type' => 'product_grid', 'source' => 'latest', 'title' => 'Draft Section',
            'is_active' => true, 'status' => 'draft',
        ]);

        // Live homepage never shows a draft (falls back to the default composition here).
        $this->get(route('home'))->assertOk()->assertDontSee('Draft Section');

        // The signed preview includes drafts + shows the preview ribbon.
        $preview = URL::temporarySignedRoute('storefront.preview', now()->addHour());
        $this->get($preview)->assertOk()->assertSee('Draft Section')->assertSee('Preview mode', false);
    }

    public function test_the_preview_route_rejects_an_unsigned_request(): void
    {
        $this->get(route('storefront.preview'))->assertForbidden();
    }

    public function test_a_flash_sale_section_shows_a_countdown_and_urgency(): void
    {
        Product::factory()->stock(3)->create(['name' => 'Almost Gone']); // low stock → urgency

        HomepageSection::create([
            'type' => 'countdown_deal', 'source' => 'latest', 'title' => 'Weekend Flash',
            'ends_at' => now()->addDay(), 'item_limit' => 8, 'is_active' => true,
        ]);

        $response = $this->get(route('home'))->assertOk();
        $response->assertSee('Weekend Flash');
        $response->assertSee('gb-flash-countdown', false); // live countdown element
        $response->assertSee('Only 3 left');               // urgency bar
    }
}
