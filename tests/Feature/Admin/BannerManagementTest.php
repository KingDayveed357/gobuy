<?php

namespace Tests\Feature\Admin;

use App\Modules\Marketing\Models\Banner;
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
}
