<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sitemap_lists_active_products_as_xml(): void
    {
        $active = Product::factory()->create(['name' => 'Listed Widget']);
        $draft = Product::factory()->draft()->create(['name' => 'Hidden Widget']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('<urlset', false)
            ->assertSee(route('products.show', $active->slug), false)
            ->assertSee(route('products.index'), false)
            ->assertDontSee(route('products.show', $draft->slug), false);
    }

    public function test_robots_txt_points_to_the_sitemap(): void
    {
        $response = $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.route('sitemap'), false)
            ->assertSee('Disallow: /admin', false);

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
    }

    public function test_product_page_emits_jsonld_and_canonical(): void
    {
        $product = Product::factory()->priced(5000)->stock(3)->create(['name' => 'Schema Widget']);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type": "Product"', false)
            ->assertSee('https://schema.org/InStock', false)
            ->assertSee('<link rel="canonical"', false);
    }
}
