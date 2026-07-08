<?php

namespace App\Livewire\Admin\Campaign;

use App\Modules\Marketing\Models\Campaign;
use App\Modules\Marketing\Models\HomepageSection;
use App\Modules\Marketing\Services\BlockAnalytics;
use App\Modules\Marketing\Services\CampaignService;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Reactive campaign editor — the whole edit experience in one component: identity
 * & schedule settings (dirty-aware save), one-switch Launch/End, and inline
 * management of the campaign's pricing levers and creatives (coupons, sale prices,
 * banners) with no page reloads. Sections are NOT edited here — they belong to the
 * landing Page and are edited in the page builder, which this screen links to.
 */
class Editor extends Component
{
    public Campaign $campaign;

    /** Levers the campaign switches on/off (not sections — those live on the Page). */
    private const LEVER_TYPES = ['coupon', 'promo', 'banner'];

    // Settings form.
    public string $name = '';

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public ?string $accentColor = null;

    public ?string $badgeText = null;

    // Per-lever "add" search terms.
    public string $couponSearch = '';

    public string $promoSearch = '';

    public string $bannerSearch = '';

    public function mount(Campaign $campaign): void
    {
        $this->campaign = $campaign;
        $this->name = $campaign->name;
        $this->startsAt = optional($campaign->starts_at)->format('Y-m-d\TH:i');
        $this->endsAt = optional($campaign->ends_at)->format('Y-m-d\TH:i');
        $this->accentColor = $campaign->accent_color;
        $this->badgeText = $campaign->badge_text;
    }

    /** Has the settings form drifted from what is stored? Drives the Save button. */
    public function getDirtyProperty(): bool
    {
        return $this->name !== $this->campaign->name
            || ($this->startsAt ?: null) !== optional($this->campaign->starts_at)->format('Y-m-d\TH:i')
            || ($this->endsAt ?: null) !== optional($this->campaign->ends_at)->format('Y-m-d\TH:i')
            || ($this->accentColor ?: null) !== ($this->campaign->accent_color ?: null)
            || ($this->badgeText ?: null) !== ($this->campaign->badge_text ?: null);
    }

    public function saveSettings(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'accentColor' => ['nullable', 'string', 'max:20'],
            'badgeText' => ['nullable', 'string', 'max:30'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
        ], attributes: ['startsAt' => 'start date', 'endsAt' => 'end date']);

        $this->campaign->update([
            'name' => $this->name,
            'accent_color' => $this->accentColor ?: null,
            'badge_text' => $this->badgeText ?: null,
            'starts_at' => $this->startsAt ?: null,
            'ends_at' => $this->endsAt ?: null,
        ]);
        $this->campaign->refresh();

        $this->toast('success', 'Campaign settings saved.');
    }

    public function launch(CampaignService $service): void
    {
        $service->launch($this->campaign);
        $this->campaign->refresh();
        $this->toast('success', 'Campaign launched — every member is live.');
    }

    public function end(CampaignService $service): void
    {
        $service->end($this->campaign);
        $this->campaign->refresh();
        $this->toast('warning', 'Campaign ended — every member is switched off.');
    }

    public function attach(CampaignService $service, string $type, int $id): void
    {
        abort_unless(in_array($type, self::LEVER_TYPES, true), 403);
        $service->attachMember($this->campaign, $type, $id);
        $this->campaign->refresh();
        $this->resetSearch($type);
        $this->toast('success', 'Added to campaign.');
    }

    public function detach(CampaignService $service, string $type, int $id): void
    {
        abort_unless(in_array($type, self::LEVER_TYPES, true), 403);
        $service->detachMember($this->campaign, $type, $id);
        $this->campaign->refresh();
        $this->toast('info', 'Removed from campaign.');
    }

    private function resetSearch(string $type): void
    {
        match ($type) {
            'coupon' => $this->couponSearch = '',
            'promo' => $this->promoSearch = '',
            'banner' => $this->bannerSearch = '',
            default => null,
        };
    }

    private function toast(string $type, string $message): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    /**
     * Unassigned library entities of a type, filtered by the lever's search box.
     */
    private function candidates(CampaignService $service, string $type, string $search): Collection
    {
        $column = match ($type) {
            'coupon' => 'code',
            'banner' => 'title',
            default => 'label',
        };

        $query = $service->memberModel($type)::query()->whereNull('campaign_id');

        if (trim($search) !== '') {
            $query->where($column, 'like', '%'.trim($search).'%');
        }

        return $query->orderBy($column)->limit(8)->get();
    }

    public function render(BlockAnalytics $analytics, CampaignService $service)
    {
        $page = $this->campaign->page;

        return view('livewire.admin.campaign.editor', [
            'analytics' => $analytics->forCampaign($this->campaign),
            'pageSections' => $page
                ? HomepageSection::forPlacement($page->slug)->orderBy('sort_order')->get()
                : collect(),
            'coupons' => $this->campaign->coupons()->orderBy('code')->get(),
            'promos' => $this->campaign->promotionalPrices()->latest()->get(),
            'banners' => $this->campaign->banners()->orderBy('title')->get(),
            'couponCandidates' => $this->candidates($service, 'coupon', $this->couponSearch),
            'promoCandidates' => $this->candidates($service, 'promo', $this->promoSearch),
            'bannerCandidates' => $this->candidates($service, 'banner', $this->bannerSearch),
        ]);
    }
}
