<?php

namespace Tests\Feature\Marketing;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Models\ProductCollection;
use App\Modules\Marketing\Services\SectionValidator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SectionValidatorTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function validator(): SectionValidator
    {
        return app(SectionValidator::class);
    }

    private function section(array $attributes): HomepageSection
    {
        return new HomepageSection($attributes);
    }

    public function test_an_editorial_block_with_no_copy_is_not_publishable(): void
    {
        $empty = $this->section(['type' => 'rich_text', 'settings' => []]);
        $this->assertFalse($this->validator()->isPublishable($empty));

        $filled = $this->section(['type' => 'rich_text', 'title' => 'Our Story']);
        $this->assertTrue($this->validator()->isPublishable($filled));
    }

    public function test_an_image_block_needs_an_image(): void
    {
        $noImage = $this->section(['type' => 'editorial_media', 'title' => 'Look', 'settings' => []]);
        $this->assertFalse($this->validator()->isPublishable($noImage));

        $withImage = $this->section(['type' => 'editorial_media', 'title' => 'Look', 'settings' => ['image_url' => '/x.jpg']]);
        $this->assertTrue($this->validator()->isPublishable($withImage));
    }

    public function test_a_product_block_without_a_source_is_not_publishable(): void
    {
        $problems = $this->validator()->problems($this->section(['type' => 'product_grid']));

        $this->assertNotEmpty($problems);
        $this->assertStringContainsString('where the products come from', $problems[0]);
    }

    public function test_a_ref_source_without_a_ref_is_not_publishable(): void
    {
        $problems = $this->validator()->problems($this->section(['type' => 'product_grid', 'source' => 'category']));

        $this->assertNotEmpty($problems);
        $this->assertStringContainsString('category', $problems[0]);
    }

    public function test_a_manual_source_with_an_empty_collection_is_not_publishable(): void
    {
        $collection = ProductCollection::create(['name' => 'Empty Edit', 'is_active' => true]);

        $section = $this->section(['type' => 'product_grid', 'source' => 'manual', 'source_ref' => (string) $collection->id, 'item_limit' => 8]);

        $this->assertFalse($this->validator()->isPublishable($section));
    }

    public function test_a_dynamic_product_block_with_stock_is_publishable(): void
    {
        Product::factory()->stock(5)->create();

        $section = $this->section(['type' => 'product_grid', 'source' => 'latest', 'item_limit' => 8]);

        $this->assertTrue($this->validator()->isPublishable($section));
    }

    public function test_a_category_grid_needs_active_categories(): void
    {
        $emptyValidator = $this->validator();
        $this->assertFalse($emptyValidator->isPublishable($this->section(['type' => 'category_grid', 'item_limit' => 8])));

        Category::factory()->create();
        $this->assertTrue($this->validator()->isPublishable($this->section(['type' => 'category_grid', 'item_limit' => 8])));
    }

    public function test_a_banner_row_needs_a_live_banner_in_its_slot(): void
    {
        $section = $this->section(['type' => 'banner_row', 'source_ref' => 'home_hero']);
        $this->assertFalse($this->validator()->isPublishable($section));

        Banner::create(['title' => 'Hero', 'placement' => 'home_hero', 'is_active' => true]);
        $this->assertTrue($this->validator()->isPublishable($section));
    }
}
