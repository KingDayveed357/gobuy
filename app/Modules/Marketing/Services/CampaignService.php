<?php

namespace App\Modules\Marketing\Services;

use App\Modules\Marketing\Enums\SectionStatus;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\Campaign;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Models\Page;
use App\Modules\Pricing\Models\Coupon;
use App\Modules\Pricing\Models\PromotionalPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Launches and ends campaigns atomically — the single switch that activates (or
 * kills) every member (sections, banners, coupons, promo prices, landing page)
 * on the campaign's schedule, replacing four independently-managed windows.
 */
class CampaignService
{
    /**
     * Activate every member on the campaign's schedule and take the campaign live
     * (or scheduled, if it starts in the future).
     */
    public function launch(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $starts = $campaign->starts_at;
            $ends = $campaign->ends_at;

            $campaign->sections()->update(['status' => SectionStatus::Published->value, 'is_active' => true, 'starts_at' => $starts, 'ends_at' => $ends]);
            $campaign->banners()->update(['is_active' => true, 'starts_at' => $starts, 'ends_at' => $ends]);
            $campaign->coupons()->update(['is_active' => true, 'starts_at' => $starts, 'expires_at' => $ends]); // coupons use expires_at
            $campaign->promotionalPrices()->update(['is_active' => true, 'starts_at' => $starts, 'ends_at' => $ends]);

            if ($campaign->page && ! $campaign->page->isHome()) {
                $campaign->page->update(['status' => Page::STATUS_PUBLISHED]);
            }

            $campaign->update([
                'status' => ($starts && $starts->isFuture()) ? Campaign::STATUS_SCHEDULED : Campaign::STATUS_LIVE,
            ]);
        });

        $this->flush($campaign);
    }

    /** Immediately deactivate every member and mark the campaign ended. */
    public function end(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $campaign->sections()->update(['is_active' => false]);
            $campaign->banners()->update(['is_active' => false]);
            $campaign->coupons()->update(['is_active' => false]);
            $campaign->promotionalPrices()->update(['is_active' => false]);

            if ($campaign->page && ! $campaign->page->isHome()) {
                $campaign->page->update(['status' => Page::STATUS_DRAFT]);
            }

            $campaign->update(['status' => Campaign::STATUS_ENDED]);
        });

        $this->flush($campaign);
    }

    /**
     * Stamp out a ready-to-edit campaign from a template: a draft landing page +
     * pre-wired draft sections, all tagged to the new campaign.
     */
    public function createFromTemplate(string $template, string $name): Campaign
    {
        return DB::transaction(function () use ($template, $name): Campaign {
            $page = Page::create([
                'title' => $name,
                'slug' => $this->uniquePageSlug(Str::slug($name)),
                'status' => Page::STATUS_DRAFT,
            ]);

            $campaign = Campaign::create([
                'name' => $name,
                'status' => Campaign::STATUS_DRAFT,
                'page_id' => $page->id,
                'badge_text' => self::TEMPLATES[$template]['badge'] ?? null,
                'accent_color' => self::TEMPLATES[$template]['accent'] ?? null,
            ]);

            foreach (self::TEMPLATES[$template]['sections'] ?? self::TEMPLATES['seasonal']['sections'] as $i => $section) {
                HomepageSection::create($section + [
                    'placement' => $page->slug,
                    'campaign_id' => $campaign->id,
                    'status' => SectionStatus::Draft->value,
                    'is_active' => true,
                    'sort_order' => $i,
                ]);
            }

            return $campaign;
        });
    }

    /** Member kinds that can be tagged to a campaign. */
    public const MEMBER_TYPES = ['section', 'banner', 'coupon', 'promo'];

    /** Tag a library entity (banner/coupon/promo/section) to this campaign. */
    public function attachMember(Campaign $campaign, string $type, int $memberId): void
    {
        $this->memberModel($type)::whereKey($memberId)->update(['campaign_id' => $campaign->id]);
    }

    /** Untag a member — it survives, just no longer coordinated by this campaign. */
    public function detachMember(Campaign $campaign, string $type, int $memberId): void
    {
        $this->memberModel($type)::whereKey($memberId)
            ->where('campaign_id', $campaign->id)
            ->update(['campaign_id' => null]);
    }

    /**
     * @return class-string<Model>
     */
    public function memberModel(string $type): string
    {
        return match ($type) {
            'section' => HomepageSection::class,
            'banner' => Banner::class,
            'coupon' => Coupon::class,
            'promo' => PromotionalPrice::class,
        };
    }

    private function flush(Campaign $campaign): void
    {
        HomepageMerchandiser::forget();
        if ($campaign->page && ! $campaign->page->isHome()) {
            HomepageMerchandiser::forget($campaign->page->slug);
        }
    }

    private function uniquePageSlug(string $base): string
    {
        $slug = $base ?: 'campaign';
        $i = 1;
        while (Page::where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$i;
        }

        return $slug;
    }

    /**
     * Campaign blueprints — pre-wired draft section sets.
     *
     * @var array<string, array{label: string, badge: ?string, accent: ?string, sections: list<array<string, mixed>>}>
     */
    public const TEMPLATES = [
        'flash_sale' => [
            'label' => 'Flash Sale', 'badge' => 'FLASH SALE', 'accent' => '#e63757',
            'sections' => [
                ['type' => 'countdown_deal', 'source' => 'best_sellers', 'title' => 'Flash sale — ends soon', 'item_limit' => 8, 'cta_label' => 'Shop all'],
                ['type' => 'product_grid', 'source' => 'latest', 'title' => 'More deals', 'item_limit' => 12],
            ],
        ],
        'brand_week' => [
            'label' => 'Brand Week', 'badge' => 'BRAND WEEK', 'accent' => '#3874ff',
            'sections' => [
                ['type' => 'brand_rail', 'source' => null, 'title' => 'Featured brands', 'item_limit' => 12],
                ['type' => 'product_rail', 'source' => 'featured', 'title' => 'Top picks', 'item_limit' => 8, 'cta_label' => 'Shop all'],
                ['type' => 'product_grid', 'source' => 'latest', 'title' => 'New in', 'item_limit' => 12],
            ],
        ],
        'seasonal' => [
            'label' => 'Seasonal Story', 'badge' => null, 'accent' => '#f5803e',
            'sections' => [
                ['type' => 'category_grid', 'source' => null, 'title' => 'Shop by category', 'item_limit' => 12],
                ['type' => 'product_rail', 'source' => 'featured', 'title' => "Editor's picks", 'item_limit' => 8, 'cta_label' => 'Shop all'],
                ['type' => 'product_grid', 'source' => 'latest', 'title' => 'Just landed', 'item_limit' => 12],
            ],
        ],
    ];
}
