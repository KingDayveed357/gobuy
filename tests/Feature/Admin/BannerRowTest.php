<?php

namespace Tests\Feature\Admin;

use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Services\HomepageMerchandiser;
use App\Modules\Marketing\Services\SectionValidator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class BannerRowTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_a_banner_row_renders_its_chosen_banners_in_order(): void
    {
        $alpha = Banner::create(['title' => 'Alpha', 'placement' => 'home_hero', 'is_active' => true]);
        $beta = Banner::create(['title' => 'Beta', 'placement' => 'home_strip', 'is_active' => true]);

        // Chosen in a deliberate order that ignores placement buckets entirely.
        $section = HomepageSection::create([
            'type' => 'banner_row', 'is_active' => true, 'status' => 'published',
            'settings' => ['banner_ids' => [$beta->id, $alpha->id]],
        ]);

        $items = app(HomepageMerchandiser::class)->resolveSection($section)->items;

        $this->assertSame([$beta->id, $alpha->id], $items->pluck('id')->all());
    }

    public function test_a_banner_row_drops_a_chosen_banner_that_is_no_longer_live(): void
    {
        $live = Banner::create(['title' => 'Live', 'placement' => 'home_hero', 'is_active' => true]);
        $hidden = Banner::create(['title' => 'Hidden', 'placement' => 'home_hero', 'is_active' => false]);

        $section = HomepageSection::create([
            'type' => 'banner_row', 'is_active' => true, 'status' => 'published',
            'settings' => ['banner_ids' => [$hidden->id, $live->id]],
        ]);

        $items = app(HomepageMerchandiser::class)->resolveSection($section)->items;

        $this->assertSame([$live->id], $items->pluck('id')->all());
    }

    public function test_a_banner_row_with_chosen_banners_is_publishable(): void
    {
        $banner = Banner::create(['title' => 'Hero', 'placement' => 'home_hero', 'is_active' => true]);
        $section = new HomepageSection(['type' => 'banner_row', 'settings' => ['banner_ids' => [$banner->id]]]);

        $this->assertTrue(app(SectionValidator::class)->isPublishable($section));
    }

    public function test_a_banner_row_with_only_hidden_banners_is_not_publishable(): void
    {
        $hidden = Banner::create(['title' => 'Hidden', 'placement' => 'home_hero', 'is_active' => false]);
        $section = new HomepageSection(['type' => 'banner_row', 'settings' => ['banner_ids' => [$hidden->id]]]);

        $this->assertFalse(app(SectionValidator::class)->isPublishable($section));
    }

    public function test_the_banner_search_endpoint_returns_matches(): void
    {
        Banner::create(['title' => 'Weekend Flash Sale', 'placement' => 'home_hero', 'is_active' => true]);
        Banner::create(['title' => 'Unrelated Promo', 'placement' => 'home_strip', 'is_active' => true]);

        $this->getJson(route('admin.banners.search', ['q' => 'Weekend']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Weekend Flash Sale', 'live' => true]);
    }

    public function test_quick_create_makes_a_live_banner_without_leaving(): void
    {
        $this->postJson(route('admin.banners.quick-store'), [
            'title' => 'Quick Hero', 'placement' => 'home_hero', 'theme' => 'rose',
        ])->assertOk()->assertJsonFragment(['title' => 'Quick Hero', 'live' => true]);

        $this->assertDatabaseHas('banners', ['title' => 'Quick Hero', 'placement' => 'home_hero', 'is_active' => 1]);
    }

    public function test_the_builder_persists_the_chosen_banner_ids(): void
    {
        $banner = Banner::create(['title' => 'Chosen', 'placement' => 'home_hero', 'is_active' => true]);

        $this->post(route('admin.merchandising.store'), [
            'type' => 'banner_row', 'status' => 'published', 'is_active' => 1,
            'settings' => ['banner_ids' => [$banner->id]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $section = HomepageSection::firstWhere('type', 'banner_row');
        $this->assertSame([$banner->id], $section->bannerIds());
    }

    public function test_publishing_a_banner_row_with_no_banners_is_blocked(): void
    {
        $this->post(route('admin.merchandising.store'), [
            'type' => 'banner_row', 'status' => 'published', 'is_active' => 1,
        ])->assertSessionHasErrors('publish');

        $this->assertDatabaseCount('homepage_sections', 0);
    }
}
