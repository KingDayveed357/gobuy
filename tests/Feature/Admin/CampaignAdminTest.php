<?php

namespace Tests\Feature\Admin;

use App\Modules\Catalog\Models\Product;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\Campaign;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Models\Page;
use App\Modules\Pricing\Models\Coupon;
use App\Modules\Pricing\Models\PromotionalPrice;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class CampaignAdminTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin('Super Admin');
    }

    public function test_admin_can_scaffold_a_campaign_from_a_template(): void
    {
        $this->post(route('admin.campaigns.from-template'), [
            'template' => 'flash_sale',
            'name' => 'Black Friday 2026',
        ])->assertRedirect();

        $campaign = Campaign::firstWhere('name', 'Black Friday 2026');
        $this->assertNotNull($campaign);
        $this->assertSame(Campaign::STATUS_DRAFT, $campaign->status);
        $this->assertSame('FLASH SALE', $campaign->badge_text);

        // A draft landing page + the template's two draft sections, all wired to the campaign.
        $this->assertNotNull($campaign->page);
        $this->assertSame(Page::STATUS_DRAFT, $campaign->page->status);
        $this->assertCount(2, $campaign->sections);
        $this->assertTrue($campaign->sections->every(fn ($s) => $s->status->value === 'draft' && $s->placement === $campaign->page->slug));
    }

    public function test_an_invalid_template_is_rejected(): void
    {
        $this->post(route('admin.campaigns.from-template'), ['template' => 'nonsense', 'name' => 'X'])
            ->assertSessionHasErrors('template');
    }

    public function test_launch_activates_every_member_on_the_campaign_schedule(): void
    {
        $starts = now()->subMinute();
        $ends = now()->addWeek();
        $campaign = Campaign::create(['name' => 'Mega Sale', 'starts_at' => $starts, 'ends_at' => $ends]);

        $section = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Deals', 'is_active' => false, 'status' => 'draft', 'campaign_id' => $campaign->id]);
        $banner = Banner::create(['title' => 'Sale Banner', 'placement' => 'home_hero', 'is_active' => false, 'campaign_id' => $campaign->id]);
        $coupon = Coupon::factory()->inactive()->create(['campaign_id' => $campaign->id]);
        $promo = $this->makePromo(['is_active' => false, 'campaign_id' => $campaign->id]);

        $this->post(route('admin.campaigns.launch', $campaign))->assertRedirect();

        $this->assertSame(Campaign::STATUS_LIVE, $campaign->fresh()->status);

        $section->refresh();
        $this->assertTrue($section->is_active);
        $this->assertSame('published', $section->status->value);
        $this->assertEquals($ends->timestamp, $section->ends_at->timestamp);

        $this->assertTrue($banner->fresh()->is_active);
        $this->assertEquals($ends->timestamp, $banner->fresh()->ends_at->timestamp);

        $coupon->refresh();
        $this->assertTrue($coupon->is_active);
        $this->assertEquals($ends->timestamp, $coupon->expires_at->timestamp); // coupons schedule via expires_at

        $this->assertTrue($promo->fresh()->is_active);
        $this->assertEquals($ends->timestamp, $promo->fresh()->ends_at->timestamp);
    }

    public function test_launch_with_a_future_start_marks_the_campaign_scheduled(): void
    {
        $campaign = Campaign::create(['name' => 'Coming Soon', 'starts_at' => now()->addWeek()]);
        $this->post(route('admin.campaigns.launch', $campaign))->assertRedirect();

        $this->assertSame(Campaign::STATUS_SCHEDULED, $campaign->fresh()->status);
    }

    public function test_end_deactivates_every_member(): void
    {
        $campaign = Campaign::create(['name' => 'Winding Down', 'status' => Campaign::STATUS_LIVE]);
        $section = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Deals', 'is_active' => true, 'status' => 'published', 'campaign_id' => $campaign->id]);
        $banner = Banner::create(['title' => 'Sale Banner', 'placement' => 'home_hero', 'is_active' => true, 'campaign_id' => $campaign->id]);
        $coupon = Coupon::factory()->create(['campaign_id' => $campaign->id]);
        $promo = $this->makePromo(['is_active' => true, 'campaign_id' => $campaign->id]);

        $this->post(route('admin.campaigns.end', $campaign))->assertRedirect();

        $this->assertSame(Campaign::STATUS_ENDED, $campaign->fresh()->status);
        $this->assertFalse($section->fresh()->is_active);
        $this->assertFalse($banner->fresh()->is_active);
        $this->assertFalse($coupon->fresh()->is_active);
        $this->assertFalse($promo->fresh()->is_active);
    }

    public function test_admin_can_attach_and_detach_a_member(): void
    {
        $campaign = Campaign::create(['name' => 'Assemble']);
        $section = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Loose', 'is_active' => true]);

        $this->post(route('admin.campaigns.attach', $campaign), ['member_type' => 'section', 'member_id' => $section->id])->assertRedirect();
        $this->assertSame($campaign->id, $section->fresh()->campaign_id);

        $this->post(route('admin.campaigns.detach', $campaign), ['member_type' => 'section', 'member_id' => $section->id])->assertRedirect();
        $this->assertNull($section->fresh()->campaign_id);
    }

    public function test_deleting_a_campaign_keeps_its_members(): void
    {
        $campaign = Campaign::create(['name' => 'Doomed']);
        $section = HomepageSection::create(['type' => 'product_grid', 'source' => 'latest', 'title' => 'Survivor', 'is_active' => true, 'campaign_id' => $campaign->id]);

        $this->delete(route('admin.campaigns.destroy', $campaign))->assertRedirect();

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
        $this->assertDatabaseHas('homepage_sections', ['id' => $section->id, 'campaign_id' => null]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makePromo(array $attributes): PromotionalPrice
    {
        $variant = Product::factory()->stock(5)->create()->primaryVariant();

        return PromotionalPrice::create($attributes + [
            'product_variant_id' => $variant->id,
            'label' => 'Promo',
            'price' => 1000,
        ]);
    }
}
