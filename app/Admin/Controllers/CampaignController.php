<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Models\Banner;
use App\Modules\Marketing\Models\Campaign;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Services\BlockAnalytics;
use App\Modules\Marketing\Services\CampaignService;
use App\Modules\Pricing\Models\Coupon;
use App\Modules\Pricing\Models\PromotionalPrice;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function __construct(private readonly CampaignService $campaigns) {}

    public function index(): View
    {
        return view('admin.campaigns.index', [
            'campaigns' => Campaign::with('page')->latest()->get(),
            'templates' => CampaignService::TEMPLATES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Campaign::create($this->validated($request));

        return back()->with('status', 'Campaign created. Add members, then launch.');
    }

    public function fromTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'template' => ['required', Rule::in(array_keys(CampaignService::TEMPLATES))],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $campaign = $this->campaigns->createFromTemplate($data['template'], $data['name']);

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('status', 'Campaign scaffolded from template — review the drafts, then launch.');
    }

    public function show(Campaign $campaign, BlockAnalytics $analytics): View
    {
        $campaign->load(['page', 'sections', 'banners', 'coupons', 'promotionalPrices']);

        return view('admin.campaigns.show', [
            'campaign' => $campaign,
            'analytics' => $analytics->forCampaign($campaign),
            'candidates' => [
                'section' => HomepageSection::whereNull('campaign_id')->orderBy('title')->get(['id', 'title']),
                'banner' => Banner::whereNull('campaign_id')->orderBy('title')->get(['id', 'title']),
                'coupon' => Coupon::whereNull('campaign_id')->orderBy('code')->get(['id', 'code']),
                'promo' => PromotionalPrice::whereNull('campaign_id')->latest()->get(['id', 'label']),
            ],
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $campaign->update($this->validated($request));

        return back()->with('status', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete(); // members' campaign_id is nulled by the FK (they survive)

        return redirect()->route('admin.campaigns.index')->with('status', 'Campaign removed.');
    }

    public function launch(Campaign $campaign): RedirectResponse
    {
        $this->campaigns->launch($campaign);

        return back()->with('status', 'Campaign launched — all members are live.');
    }

    public function end(Campaign $campaign): RedirectResponse
    {
        $this->campaigns->end($campaign);

        return back()->with('status', 'Campaign ended — all members deactivated.');
    }

    public function attach(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $request->validate([
            'member_type' => ['required', 'in:section,banner,coupon,promo'],
            'member_id' => ['required', 'integer'],
        ]);

        $this->memberModel($data['member_type'])::whereKey($data['member_id'])->update(['campaign_id' => $campaign->id]);

        return back()->with('status', 'Added to campaign.');
    }

    public function detach(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $request->validate([
            'member_type' => ['required', 'in:section,banner,coupon,promo'],
            'member_id' => ['required', 'integer'],
        ]);

        $this->memberModel($data['member_type'])::whereKey($data['member_id'])
            ->where('campaign_id', $campaign->id)
            ->update(['campaign_id' => null]);

        return back()->with('status', 'Removed from campaign.');
    }

    /**
     * @return class-string<Model>
     */
    private function memberModel(string $type): string
    {
        return match ($type) {
            'section' => HomepageSection::class,
            'banner' => Banner::class,
            'coupon' => Coupon::class,
            'promo' => PromotionalPrice::class,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'badge_text' => ['nullable', 'string', 'max:30'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }
}
