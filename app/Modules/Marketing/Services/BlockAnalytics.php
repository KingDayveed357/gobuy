<?php

namespace App\Modules\Marketing\Services;

use App\Modules\Marketing\Models\BlockEvent;
use App\Modules\Marketing\Models\Campaign;
use Illuminate\Support\Collection;

/**
 * Turns raw {@see BlockEvent} rows into the numbers merchandisers act on:
 * impressions, clicks and click-through rate per section (and per campaign).
 */
class BlockAnalytics
{
    /**
     * Impression/click/CTR totals keyed by section id, for the given sections.
     *
     * @param  iterable<int>  $sectionIds
     * @return Collection<int, array{impressions: int, clicks: int, ctr: float}>
     */
    public function forSections(iterable $sectionIds): Collection
    {
        $ids = collect($sectionIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return BlockEvent::query()
            ->selectRaw('homepage_section_id')
            ->selectRaw('sum(type = ?) as impressions', [BlockEvent::TYPE_IMPRESSION])
            ->selectRaw('sum(type = ?) as clicks', [BlockEvent::TYPE_CLICK])
            ->whereIn('homepage_section_id', $ids)
            ->groupBy('homepage_section_id')
            ->get()
            ->mapWithKeys(function (BlockEvent $row): array {
                $impressions = (int) $row->impressions;
                $clicks = (int) $row->clicks;

                return [$row->homepage_section_id => $this->summarise($impressions, $clicks)];
            });
    }

    /**
     * Rolled-up totals across every section that belongs to the campaign.
     *
     * @return array{impressions: int, clicks: int, ctr: float}
     */
    public function forCampaign(Campaign $campaign): array
    {
        $stats = $this->forSections($campaign->sections()->pluck('id'));

        return $this->summarise(
            (int) $stats->sum('impressions'),
            (int) $stats->sum('clicks'),
        );
    }

    /**
     * @return array{impressions: int, clicks: int, ctr: float}
     */
    private function summarise(int $impressions, int $clicks): array
    {
        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? round($clicks / $impressions * 100, 1) : 0.0,
        ];
    }
}
