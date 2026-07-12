@props([
    'items' => [],       // list of ['label' => string, 'value' => number, 'sub' => ?string]
    'total' => null,     // grand total for the "% of" contribution column (optional)
    'format' => 'money', // 'money' | 'number'
    'emptyText' => 'No data for this period.',
    'emptyIcon' => 'fa-chart-simple',
])

@php
    $items = collect($items)->values();
    $max = (float) ($items->max('value') ?: 1);
    $fmt = fn ($v) => $format === 'money' ? money($v) : number_format((float) $v);
@endphp

{{--
    A ranked "data bar" list — the executive-BI alternative to a bar chart for
    "top N" rankings. Each row shows rank, label, an inline magnitude bar (width =
    share of the top item) and the value, plus its % contribution to the total.
    Scales to any dataset (pass the top N + an "Others" row) and needs no chart
    library, so it stays fast and fully accessible.
--}}
<div class="gb-rbl">
    @forelse ($items as $i => $item)
        @php
            $value = (float) ($item['value'] ?? 0);
            $width = max(2, (int) round($value / $max * 100));
            $share = $total > 0 ? round($value / $total * 100, 1) : null;
            $isOthers = ($item['others'] ?? false);
        @endphp
        <div class="gb-rbl-row {{ $isOthers ? 'gb-rbl-row--others' : '' }}">
            <span class="gb-rbl-rank">{{ $isOthers ? '·' : $i + 1 }}</span>
            <div class="gb-rbl-main">
                <div class="gb-rbl-head">
                    <span class="gb-rbl-label text-truncate">{{ $item['label'] ?? '—' }}</span>
                    <span class="gb-rbl-value">{{ $fmt($value) }}</span>
                </div>
                <div class="gb-rbl-track">
                    <div class="gb-rbl-bar" style="width: {{ $width }}%"></div>
                </div>
                <div class="gb-rbl-meta">
                    <span>{{ $item['sub'] ?? '' }}</span>
                    @if ($share !== null)
                        <span class="gb-rbl-share">{{ $share }}% of total</span>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <x-admin.empty-state :icon="$emptyIcon" :text="$emptyText" compact />
    @endforelse
</div>

@once
    @push('styles')
        <style>
            [x-cloak] { display: none !important; }
            .gb-rbl-row { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.6rem 0; }
            .gb-rbl-row + .gb-rbl-row { border-top: 1px solid var(--phoenix-border-color-translucent, rgba(0,0,0,.06)); }
            .gb-rbl-row--others { opacity: 0.75; }
            .gb-rbl-rank {
                flex: 0 0 1.4rem; text-align: center;
                font-size: 0.75rem; font-weight: 700;
                color: var(--phoenix-secondary-color, #6c757d);
                line-height: 1.4rem;
            }
            .gb-rbl-main { flex: 1 1 auto; min-width: 0; }
            .gb-rbl-head { display: flex; justify-content: space-between; gap: 0.5rem; align-items: baseline; }
            .gb-rbl-label { font-size: 0.85rem; font-weight: 600; color: var(--phoenix-emphasis-color, #141824); min-width: 0; }
            .gb-rbl-value { font-size: 0.85rem; font-weight: 700; color: var(--phoenix-emphasis-color, #141824); white-space: nowrap; }
            .gb-rbl-track {
                height: 6px; margin: 0.35rem 0 0.3rem;
                background: var(--phoenix-secondary-bg, #eef0f2);
                border-radius: 999px; overflow: hidden;
            }
            .gb-rbl-bar {
                height: 100%; border-radius: 999px;
                background: linear-gradient(90deg, var(--phoenix-primary, #3874ff), var(--phoenix-info, #0097eb));
            }
            .gb-rbl-meta { display: flex; justify-content: space-between; gap: 0.5rem; font-size: 0.72rem; color: var(--phoenix-secondary-color, #6c757d); }
            .gb-rbl-share { font-weight: 600; white-space: nowrap; }
        </style>
    @endpush
@endonce
