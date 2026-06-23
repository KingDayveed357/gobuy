<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Services\CategoryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CategoryCacheTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creating_a_category_busts_the_nav_cache(): void
    {
        Cache::put(CategoryService::NAV_CACHE_KEY, 'stale', 3600);

        app(CategoryService::class)->create(['name' => 'Fresh Category']);

        $this->assertFalse(Cache::has(CategoryService::NAV_CACHE_KEY));
    }

    public function test_updating_a_category_busts_the_nav_cache(): void
    {
        $category = Category::factory()->create();
        Cache::put(CategoryService::NAV_CACHE_KEY, 'stale', 3600);

        app(CategoryService::class)->update($category, ['name' => 'Renamed']);

        $this->assertFalse(Cache::has(CategoryService::NAV_CACHE_KEY));
    }

    public function test_deleting_a_category_busts_the_nav_cache(): void
    {
        $category = Category::factory()->create();
        Cache::put(CategoryService::NAV_CACHE_KEY, 'stale', 3600);

        app(CategoryService::class)->delete($category);

        $this->assertFalse(Cache::has(CategoryService::NAV_CACHE_KEY));
    }
}
