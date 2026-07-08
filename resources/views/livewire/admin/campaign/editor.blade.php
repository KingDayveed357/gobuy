@php
    use App\Modules\Marketing\Models\Campaign;

    $tone = match ($campaign->status) {
        Campaign::STATUS_LIVE => 'success',
        Campaign::STATUS_SCHEDULED => 'primary',
        Campaign::STATUS_ENDED => 'secondary',
        default => 'warning',
    };

    $scheduleText = ($campaign->starts_at || $campaign->ends_at)
        ? (optional($campaign->starts_at)->format('M j, g:ia') ?? 'Now').' → '.(optional($campaign->ends_at)->format('M j, g:ia') ?? 'Open-ended')
        : 'No schedule set';

    // The three pricing/creative levers a campaign switches on/off. Sections are
    // NOT here — they belong to the landing Page and are edited in the builder.
    $levers = [
        [
            'type' => 'coupon', 'label' => 'Coupons', 'icon' => 'fa-ticket',
            'search' => 'couponSearch', 'items' => $coupons, 'candidates' => $couponCandidates,
            'itemLabel' => fn ($m) => $m->code, 'empty' => 'No coupons bundled yet.',
            'createRoute' => route('admin.coupons.index'),
        ],
        [
            'type' => 'promo', 'label' => 'Sale prices', 'icon' => 'fa-tags',
            'search' => 'promoSearch', 'items' => $promos, 'candidates' => $promoCandidates,
            'itemLabel' => fn ($m) => $m->label ?: ('#'.$m->id), 'empty' => 'No sale prices bundled yet.',
            'createRoute' => route('admin.promotions.index'),
        ],
        [
            'type' => 'banner', 'label' => 'Banners', 'icon' => 'fa-image',
            'search' => 'bannerSearch', 'items' => $banners, 'candidates' => $bannerCandidates,
            'itemLabel' => fn ($m) => $m->title, 'empty' => 'No banners bundled yet.',
            'createRoute' => route('admin.banners.index'),
        ],
    ];
@endphp

<div class="gb-campaign-editor">
    {{-- ── Sticky action bar: status, name, schedule + the one switch ── --}}
    <div class="gb-campaign-bar d-flex flex-wrap align-items-center gap-3 mb-4">
        <a href="{{ route('admin.campaigns.index') }}" class="btn btn-phoenix-secondary btn-sm flex-shrink-0" title="All campaigns"><span class="fas fa-arrow-left"></span></a>
        <div class="min-w-0 flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge badge-phoenix badge-phoenix-{{ $tone }} text-uppercase">{{ $campaign->status }}</span>
                <h4 class="mb-0 text-truncate">{{ $campaign->name }}</h4>
            </div>
            <p class="fs-10 text-body-tertiary mb-0"><span class="fas fa-clock me-1"></span>{{ $scheduleText }}</p>
        </div>

        @if ($this->dirty)
            <button type="button" wire:click="saveSettings" wire:loading.attr="disabled" class="btn btn-primary btn-sm flex-shrink-0">
                <span wire:loading.remove wire:target="saveSettings"><span class="fas fa-floppy-disk me-1"></span>Save changes</span>
                <span wire:loading wire:target="saveSettings"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span>
            </button>
        @endif

        @if ($campaign->status === Campaign::STATUS_LIVE)
            <button type="button" wire:click="end" wire:confirm="End this campaign now? Every member is switched off." class="btn btn-phoenix-danger btn-sm flex-shrink-0"><span class="fas fa-stop me-1"></span>End now</button>
        @else
            <button type="button" wire:click="launch" class="btn btn-success btn-sm flex-shrink-0" wire:loading.attr="disabled" wire:target="launch"><span class="fas fa-rocket me-1"></span>Launch</button>
        @endif
    </div>

    {{-- ── Overview tiles ── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="gb-stat-tile"><div class="gb-stat-label">Status</div><div class="gb-stat-value text-{{ $tone }}">{{ ucfirst($campaign->status) }}</div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="gb-stat-tile"><div class="gb-stat-label">Members</div><div class="gb-stat-value">{{ $campaign->memberCount() }}</div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="gb-stat-tile"><div class="gb-stat-label">Views</div><div class="gb-stat-value">{{ number_format($analytics['impressions']) }}</div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="gb-stat-tile"><div class="gb-stat-label">CTR</div><div class="gb-stat-value {{ $analytics['ctr'] >= 5 ? 'text-success' : '' }}">{{ $analytics['ctr'] }}%</div></div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        {{-- ── PRIMARY: the landing page (what shoppers actually see) ── --}}
        <div class="col-12 col-lg-7">
            <div class="card admin-card h-auto">
                <div class="card-header admin-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Landing page</h5>
                    @if ($campaign->page)
                        <span class="badge badge-phoenix badge-phoenix-{{ $campaign->page->isPublished() ? 'success' : 'warning' }}">{{ $campaign->page->isPublished() ? 'Published' : 'Draft' }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($campaign->page)
                        <p class="text-body-tertiary fs-9 mb-3">The page shoppers land on at <code>/p/{{ $campaign->page->slug }}</code>. Design its layout in the builder.</p>

                        @if ($pageSections->isNotEmpty())
                            <div class="d-flex flex-wrap gap-2 mb-4">
                                @foreach ($pageSections as $s)
                                    <span class="gb-block-chip">
                                        <span class="fas fa-grip-vertical text-body-quaternary fs-11"></span>
                                        <span class="badge badge-phoenix badge-phoenix-info fs-11">{{ $s->type->label() }}</span>
                                        <span class="fs-10 text-truncate">{{ $s->title ?: '(untitled)' }}</span>
                                        @if ($s->isDraft())<span class="fs-11 text-warning" title="Draft">●</span>@endif
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <x-admin.empty-state icon="fa-table-cells-large" text="No blocks yet — open the builder to lay out this page." />
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('admin.merchandising.index', ['page' => $campaign->page->slug]) }}" class="btn btn-primary btn-sm"><span class="fas fa-table-cells me-1"></span>Edit page content</a>
                            @if ($campaign->page->isPublished())
                                <a href="{{ $campaign->page->url() }}" target="_blank" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-external-link me-1"></span>View live page</a>
                            @endif
                        </div>
                    @else
                        <x-admin.empty-state icon="fa-file" text="This campaign has no landing page — it coordinates coupons, sale prices and banners only." />
                    @endif
                </div>
            </div>
        </div>

        {{-- ── SECONDARY: identity, schedule & branding ── --}}
        <div class="col-12 col-lg-5">
            <div class="card admin-card h-auto">
                <div class="card-header admin-card-header"><h5 class="mb-0">Settings</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Campaign name</label>
                        <input class="form-control" wire:model.blur="name" @error('name') aria-invalid="true" @enderror>
                        @error('name')<div class="text-danger fs-10 mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Starts</label><input class="form-control" type="datetime-local" wire:model.blur="startsAt"></div>
                        <div class="col-6"><label class="form-label">Ends</label><input class="form-control" type="datetime-local" wire:model.blur="endsAt">@error('endsAt')<div class="text-danger fs-10 mt-1">{{ $message }}</div>@enderror</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Accent</label><input class="form-control" wire:model.blur="accentColor" placeholder="#e63757"></div>
                        <div class="col-6"><label class="form-label">Badge</label><input class="form-control" wire:model.blur="badgeText" placeholder="SALE"></div>
                    </div>
                    <button type="button" wire:click="saveSettings" class="btn btn-primary w-100" @disabled(! $this->dirty)>
                        {{ $this->dirty ? 'Save settings' : 'Saved' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── TERTIARY: the levers this campaign switches on/off ── --}}
    <div class="row g-4 mt-1">
        @foreach ($levers as $lever)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card admin-card h-100">
                    <div class="card-header admin-card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><span class="fas {{ $lever['icon'] }} text-body-tertiary me-2"></span>{{ $lever['label'] }}</h6>
                        <span class="badge badge-phoenix badge-phoenix-secondary">{{ $lever['items']->count() }}</span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        {{-- Current members --}}
                        @forelse ($lever['items'] as $item)
                            <div class="gb-lever-item d-flex justify-content-between align-items-center">
                                <span class="text-truncate fs-9">{{ $lever['itemLabel']($item) }}</span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 lh-1"
                                        wire:click="detach('{{ $lever['type'] }}', {{ $item->id }})"
                                        wire:loading.attr="disabled" title="Remove"><span class="fas fa-xmark"></span></button>
                            </div>
                        @empty
                            <p class="fs-10 text-body-tertiary fst-italic mb-3">{{ $lever['empty'] }}</p>
                        @endforelse

                        {{-- Inline add: search library, click to bundle (no reload) --}}
                        <div class="mt-auto pt-3 border-top border-translucent">
                            <div class="position-relative">
                                <span class="fas fa-magnifying-glass position-absolute top-50 translate-middle-y ms-2 text-body-quaternary fs-10"></span>
                                <input type="text" class="form-control form-control-sm ps-4" placeholder="Search to add…"
                                       wire:model.live.debounce.300ms="{{ $lever['search'] }}">
                            </div>
                            <div class="gb-lever-candidates mt-2">
                                @forelse ($lever['candidates'] as $cand)
                                    <button type="button" class="gb-lever-add w-100 d-flex justify-content-between align-items-center"
                                            wire:click="attach('{{ $lever['type'] }}', {{ $cand->id }})" wire:loading.attr="disabled">
                                        <span class="text-truncate fs-9">{{ $lever['itemLabel']($cand) }}</span>
                                        <span class="fas fa-plus text-primary fs-10"></span>
                                    </button>
                                @empty
                                    <p class="fs-10 text-body-tertiary mb-0 px-1">
                                        No unassigned {{ strtolower($lever['label']) }}.
                                        <a href="{{ $lever['createRoute'] }}" class="text-decoration-none">Create one</a>
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
