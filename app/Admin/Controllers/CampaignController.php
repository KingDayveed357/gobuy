<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketing\Models\Campaign;
use App\Modules\Marketing\Services\CampaignService;
use Illuminate\Contracts\View\View;
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

    public function show(Campaign $campaign): View
    {
        // The page is a thin shell; the reactive editor (Livewire) owns the UI.
        return view('admin.campaigns.show', ['campaign' => $campaign]);
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

        $this->campaigns->attachMember($campaign, $data['member_type'], (int) $data['member_id']);

        return back()->with('status', 'Added to campaign.');
    }

    public function detach(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $request->validate([
            'member_type' => ['required', 'in:section,banner,coupon,promo'],
            'member_id' => ['required', 'integer'],
        ]);

        $this->campaigns->detachMember($campaign, $data['member_type'], (int) $data['member_id']);

        return back()->with('status', 'Removed from campaign.');
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
