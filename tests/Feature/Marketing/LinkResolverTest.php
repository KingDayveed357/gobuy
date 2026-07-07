<?php

namespace Tests\Feature\Marketing;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\Page;
use App\Modules\Marketing\Services\LinkResolver;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LinkResolverTest extends TestCase
{
    use LazilyRefreshDatabase;

    private LinkResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(LinkResolver::class);
    }

    public function test_it_resolves_each_link_type(): void
    {
        $product = Product::factory()->create(['name' => 'Widget']);
        $category = Category::factory()->create(['name' => 'Shoes']);
        $brand = Brand::create(['name' => 'Nike', 'slug' => 'nike', 'is_active' => true]);

        $this->assertSame(route('products.show', $product), $this->resolver->resolve(['type' => 'product', 'ref' => (string) $product->id]));
        $this->assertSame(route('products.index', ['category' => $category->slug]), $this->resolver->resolve(['type' => 'category', 'ref' => (string) $category->id]));
        $this->assertSame(route('products.index', ['brand' => 'nike']), $this->resolver->resolve(['type' => 'brand', 'ref' => (string) $brand->id]));
        $this->assertSame(route('products.index', ['q' => 'sale']), $this->resolver->resolve(['type' => 'search', 'ref' => 'sale']));
        $this->assertSame(route('products.index'), $this->resolver->resolve(['type' => 'products']));
        $this->assertSame(route('home'), $this->resolver->resolve(['type' => 'home']));
        $this->assertSame('https://example.test', $this->resolver->resolve(['type' => 'url', 'ref' => 'https://example.test']));
    }

    public function test_a_link_to_a_missing_target_is_broken(): void
    {
        $link = ['type' => 'product', 'ref' => '999999'];

        $this->assertNull($this->resolver->resolve($link));
        $this->assertTrue($this->resolver->isBroken($link));
    }

    public function test_a_link_to_an_inactive_product_is_broken(): void
    {
        $draft = Product::factory()->draft()->create();

        $this->assertTrue($this->resolver->isBroken(['type' => 'product', 'ref' => (string) $draft->id]));
    }

    public function test_url_for_falls_back_to_the_legacy_string(): void
    {
        $this->assertSame('/legacy', $this->resolver->urlFor(null, '/legacy'));
        // A broken structured link still falls back to the legacy value.
        $this->assertSame('/legacy', $this->resolver->urlFor(['type' => 'product', 'ref' => '999999'], '/legacy'));
        $this->assertNull($this->resolver->urlFor(null, null));
    }

    public function test_a_page_link_resolves_to_the_current_url_at_render_time(): void
    {
        $page = Page::create(['title' => 'Flash Weekend', 'slug' => 'flash-weekend', 'status' => Page::STATUS_PUBLISHED]);

        // Stores the ID, resolves per request — never a host baked into the DB.
        $this->assertSame(route('storefront.page', 'flash-weekend'), $this->resolver->resolve(['type' => 'page', 'ref' => (string) $page->id]));

        // A slug rename follows automatically.
        $page->update(['slug' => 'flash-weekend-2026']);
        $this->assertSame(route('storefront.page', 'flash-weekend-2026'), $this->resolver->resolve(['type' => 'page', 'ref' => (string) $page->id]));
    }

    public function test_a_link_to_a_draft_page_is_broken(): void
    {
        $page = Page::create(['title' => 'Unlaunched', 'slug' => 'unlaunched', 'status' => Page::STATUS_DRAFT]);

        $link = ['type' => 'page', 'ref' => (string) $page->id];
        $this->assertNull($this->resolver->resolve($link));
        $this->assertTrue($this->resolver->isBroken($link));
    }
}
