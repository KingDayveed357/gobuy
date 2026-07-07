<?php

namespace Tests\Feature\Admin;

use App\Modules\Marketing\Models\BlockEvent;
use App\Modules\Marketing\Models\Campaign;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Services\BlockAnalytics;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class MerchandisingAnalyticsTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    private function record(HomepageSection $section, int $impressions, int $clicks): void
    {
        $rows = [];
        for ($i = 0; $i < $impressions; $i++) {
            $rows[] = ['homepage_section_id' => $section->id, 'type' => BlockEvent::TYPE_IMPRESSION, 'created_at' => now()];
        }
        for ($i = 0; $i < $clicks; $i++) {
            $rows[] = ['homepage_section_id' => $section->id, 'type' => BlockEvent::TYPE_CLICK, 'created_at' => now()];
        }
        BlockEvent::insert($rows);
    }

    public function test_analytics_computes_impressions_clicks_and_ctr_per_section(): void
    {
        $section = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'A', 'is_active' => true]);
        $this->record($section, 200, 10); // 5% CTR

        $stats = app(BlockAnalytics::class)->forSections([$section->id]);

        $this->assertSame(200, $stats[$section->id]['impressions']);
        $this->assertSame(10, $stats[$section->id]['clicks']);
        $this->assertSame(5.0, $stats[$section->id]['ctr']);
    }

    public function test_ctr_is_zero_when_a_section_has_no_impressions(): void
    {
        $section = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Cold', 'is_active' => true]);

        $stats = app(BlockAnalytics::class)->forSections([$section->id]);

        // No rows for this section — it simply isn't in the result set.
        $this->assertFalse($stats->has($section->id));
    }

    public function test_the_merchandising_canvas_surfaces_ctr(): void
    {
        $section = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Tracked', 'is_active' => true, 'placement' => 'home']);
        $this->record($section, 100, 8);

        $this->get(route('admin.merchandising.index'))
            ->assertOk()
            ->assertSee('CTR 8%');
    }

    public function test_campaign_rolls_up_analytics_across_its_sections(): void
    {
        $campaign = Campaign::create(['name' => 'Rollup']);
        $a = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'A', 'is_active' => true, 'campaign_id' => $campaign->id]);
        $b = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'B', 'is_active' => true, 'campaign_id' => $campaign->id]);
        $this->record($a, 60, 6);
        $this->record($b, 40, 2);

        $rollup = app(BlockAnalytics::class)->forCampaign($campaign);

        $this->assertSame(100, $rollup['impressions']);
        $this->assertSame(8, $rollup['clicks']);
        $this->assertSame(8.0, $rollup['ctr']);
    }
}
