<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Campaign\Editor;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\Campaign;
use App\Modules\Pricing\Models\Coupon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class CampaignEditorTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_it_renders_the_editor_for_a_campaign(): void
    {
        $campaign = Campaign::create(['name' => 'Black Friday']);

        Livewire::test(Editor::class, ['campaign' => $campaign])
            ->assertOk()
            ->assertSee('Landing page')
            ->assertSee('Sale prices'); // relabelled "promotional prices"
    }

    public function test_the_dirty_flag_tracks_unsaved_settings_changes(): void
    {
        $campaign = Campaign::create(['name' => 'Black Friday']);

        Livewire::test(Editor::class, ['campaign' => $campaign])
            ->assertSet('dirty', false)
            ->set('name', 'Black Friday Extended')
            ->assertSet('dirty', true)
            ->call('saveSettings')
            ->assertSet('dirty', false);

        $this->assertSame('Black Friday Extended', $campaign->fresh()->name);
    }

    public function test_saving_an_empty_name_is_rejected(): void
    {
        $campaign = Campaign::create(['name' => 'Black Friday']);

        Livewire::test(Editor::class, ['campaign' => $campaign])
            ->set('name', '')
            ->call('saveSettings')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_admin_can_bundle_and_remove_a_coupon_without_a_reload(): void
    {
        $campaign = Campaign::create(['name' => 'Black Friday']);
        $coupon = Coupon::factory()->create(['code' => 'BF20']);

        Livewire::test(Editor::class, ['campaign' => $campaign])
            ->call('attach', 'coupon', $coupon->id)
            ->assertDispatched('toast');
        $this->assertSame($campaign->id, $coupon->fresh()->campaign_id);

        Livewire::test(Editor::class, ['campaign' => $campaign])
            ->call('detach', 'coupon', $coupon->id);
        $this->assertNull($coupon->fresh()->campaign_id);
    }

    public function test_the_add_search_filters_candidate_banners(): void
    {
        $campaign = Campaign::create(['name' => 'Black Friday']);
        Banner::create(['title' => 'Hero Alpha', 'placement' => 'home_hero']);
        Banner::create(['title' => 'Strip Beta', 'placement' => 'home_strip']);

        Livewire::test(Editor::class, ['campaign' => $campaign])
            ->set('bannerSearch', 'Alpha')
            ->assertSee('Hero Alpha')
            ->assertDontSee('Strip Beta');
    }

    public function test_sections_cannot_be_attached_through_the_editor(): void
    {
        // Defect A guard: the editor only manages levers; sections belong to the
        // page and are edited in the builder — attaching one here is forbidden.
        $campaign = Campaign::create(['name' => 'Black Friday']);

        Livewire::test(Editor::class, ['campaign' => $campaign])
            ->call('attach', 'section', 1)
            ->assertForbidden();
    }

    public function test_launching_from_the_editor_takes_the_campaign_live(): void
    {
        $campaign = Campaign::create(['name' => 'Black Friday', 'starts_at' => now()->subMinute()]);

        Livewire::test(Editor::class, ['campaign' => $campaign])
            ->call('launch')
            ->assertSet('campaign.status', Campaign::STATUS_LIVE);
    }
}
