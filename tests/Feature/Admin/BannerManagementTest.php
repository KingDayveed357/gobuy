<?php

namespace Tests\Feature\Admin;

use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\HomepageSection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_admin_can_create_a_banner(): void
    {
        $this->post(route('admin.banners.store'), [
            'title' => 'Mega Sale', 'subtitle' => 'Up to 50% off', 'cta_label' => 'Shop',
            'link_url' => '/products', 'placement' => 'home_hero', 'is_active' => 1, 'sort_order' => 1,
            'layout' => 'hero', 'theme' => 'indigo', 'text_theme' => 'light',
        ])->assertRedirect();

        $this->assertDatabaseHas('banners', ['title' => 'Mega Sale', 'placement' => 'home_hero', 'is_active' => 1]);
    }

    public function test_active_hero_banner_renders_on_the_homepage(): void
    {
        Banner::create(['title' => 'Homepage Promo', 'placement' => 'home_hero', 'is_active' => true]);

        $this->get(route('home'))->assertOk()->assertSee('Homepage Promo');
    }

    public function test_hidden_banner_is_not_shown(): void
    {
        Banner::create(['title' => 'Hidden Promo', 'placement' => 'home_hero', 'is_active' => false]);

        $this->get(route('home'))->assertOk()->assertDontSee('Hidden Promo');
    }

    public function test_scheduled_banner_outside_its_window_is_not_live(): void
    {
        Banner::create(['title' => 'Future Promo', 'placement' => 'home_hero', 'is_active' => true, 'starts_at' => now()->addWeek()]);
        Banner::create(['title' => 'Expired Promo', 'placement' => 'home_hero', 'is_active' => true, 'ends_at' => now()->subDay()]);
        Banner::create(['title' => 'Current Promo', 'placement' => 'home_hero', 'is_active' => true, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);

        $response = $this->get(route('home'))->assertOk();
        $response->assertSee('Current Promo');
        $response->assertDontSee('Future Promo');
        $response->assertDontSee('Expired Promo');
    }

    public function test_split_layout_banner_renders(): void
    {
        Banner::create(['title' => 'Split Banner', 'placement' => 'home_hero', 'is_active' => true, 'layout' => 'split', 'theme' => 'emerald']);

        $this->get(route('home'))->assertOk()->assertSee('Split Banner');
    }

    public function test_admin_can_create_a_banner_with_premium_options(): void
    {
        $this->post(route('admin.banners.store'), [
            'title' => 'Flash Sale', 'placement' => 'home_hero', 'is_active' => 1,
            'layout' => 'hero', 'theme' => 'amber', 'text_theme' => 'light',
            'height' => 'lg', 'content_position' => 'center', 'title_size' => 'lg',
            'cta_size' => 'lg', 'cta_radius' => 'square', 'ribbon' => '-40%',
            'countdown_to' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertDatabaseHas('banners', [
            'title' => 'Flash Sale', 'height' => 'lg', 'content_position' => 'center',
            'cta_radius' => 'square', 'ribbon' => '-40%',
        ]);
    }

    public function test_an_invalid_premium_option_is_rejected(): void
    {
        $this->post(route('admin.banners.store'), [
            'title' => 'Bad', 'placement' => 'home_hero', 'layout' => 'hero',
            'theme' => 'indigo', 'text_theme' => 'light', 'height' => 'huge',
        ])->assertSessionHasErrors('height');
    }

    public function test_ribbon_and_countdown_render_on_the_homepage(): void
    {
        Banner::create([
            'title' => 'Ribbon Promo', 'placement' => 'home_hero', 'is_active' => true,
            'ribbon' => 'HOT DEAL', 'countdown_to' => now()->addDay(),
        ]);

        $this->get(route('home'))->assertOk()
            ->assertSee('HOT DEAL')
            ->assertSee('gb-countdown', false); // live-timer element is present
    }

    public function test_a_creative_banner_renders_the_artwork_as_the_banner(): void
    {
        $banner = Banner::create([
            'title' => 'Big Campaign Artwork', 'placement' => 'home_hero',
            'is_active' => true, 'mode' => 'creative', 'cta_label' => 'Shop it',
            'link_url' => '/products',
        ]);
        $banner->addMedia(public_path('theme/img/products/3.png'))
            ->preservingOriginal()
            ->toMediaCollection(Banner::MEDIA_IMAGE);

        $response = $this->get(route('home'))->assertOk();

        // The artwork IS the banner: aspect-locked creative container, one
        // full-surface click target, the title as the accessible name…
        $response->assertSee('gb-banner--creative', false)
            ->assertSee('aria-label="Big Campaign Artwork"', false)
            ->assertSee('stretched-link', false)
            ->assertSee('Shop it');

        // …and NO composed overlays (scrim or HTML headline) on top of it.
        $response->assertDontSee('gb-banner__scrim', false)
            ->assertDontSee('gb-banner__title', false);
    }

    public function test_a_creative_banner_without_artwork_falls_back_to_composed(): void
    {
        Banner::create([
            'title' => 'No Artwork Yet', 'placement' => 'home_hero',
            'is_active' => true, 'mode' => 'creative',
        ]);

        // Never a blank block: without an upload it renders the composed layout.
        $this->get(route('home'))->assertOk()
            ->assertSee('No Artwork Yet')
            ->assertDontSee('gb-banner--creative', false);
    }

    public function test_banner_mode_is_validated_and_persisted(): void
    {
        $this->post(route('admin.banners.store'), [
            'title' => 'Creative One', 'placement' => 'home_hero', 'layout' => 'hero',
            'theme' => 'indigo', 'text_theme' => 'light', 'mode' => 'creative',
        ])->assertRedirect();
        $this->assertDatabaseHas('banners', ['title' => 'Creative One', 'mode' => 'creative']);

        $this->post(route('admin.banners.store'), [
            'title' => 'Bad Mode', 'placement' => 'home_hero', 'layout' => 'hero',
            'theme' => 'indigo', 'text_theme' => 'light', 'mode' => 'photoshop',
        ])->assertSessionHasErrors('mode');
    }

    public function test_a_banner_row_section_can_rotate_as_a_hero_carousel(): void
    {
        Banner::create(['title' => 'Slide One', 'placement' => 'home_hero', 'is_active' => true]);
        Banner::create(['title' => 'Slide Two', 'placement' => 'home_hero', 'is_active' => true]);
        HomepageSection::create([
            'type' => 'banner_row', 'source_ref' => 'home_hero', 'title' => 'Hero',
            'is_active' => true, 'status' => 'published', 'settings' => ['carousel' => '1'],
        ]);

        $this->get(route('home'))->assertOk()
            ->assertSee('gb-hero-carousel', false)
            ->assertSee('Slide One')
            ->assertSee('Slide Two');
    }

    public function test_a_single_banner_never_renders_as_a_carousel(): void
    {
        Banner::create(['title' => 'Lonely Slide', 'placement' => 'home_hero', 'is_active' => true]);
        HomepageSection::create([
            'type' => 'banner_row', 'source_ref' => 'home_hero', 'title' => 'Hero',
            'is_active' => true, 'status' => 'published', 'settings' => ['carousel' => '1'],
        ]);

        $this->get(route('home'))->assertOk()
            ->assertSee('Lonely Slide')
            ->assertDontSee('gb-hero-carousel', false);
    }
}
