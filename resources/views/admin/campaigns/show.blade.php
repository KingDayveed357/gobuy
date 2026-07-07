@extends('admin.layouts.app')

@section('title', $campaign->name.' — campaign')
@section('page-title', 'Campaign')

@section('content')
    @php($tone = match ($campaign->status) { 'live' => 'success', 'scheduled' => 'primary', 'ended' => 'secondary', default => 'warning' })

    <x-admin.page-header :title="$campaign->name" subtitle="One schedule, one switch — activate every member together.">
        <x-slot:actions>
            <a href="{{ route('admin.campaigns.index') }}" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-arrow-left me-1"></span>All campaigns</a>
            @if (! $campaign->isLive())
                <form action="{{ route('admin.campaigns.launch', $campaign) }}" method="POST" class="d-inline">@csrf
                    <button class="btn btn-success btn-sm"><span class="fas fa-rocket me-1"></span>Launch</button>
                </form>
            @else
                <form action="{{ route('admin.campaigns.end', $campaign) }}" method="POST" class="d-inline">@csrf
                    <button class="btn btn-phoenix-danger btn-sm"><span class="fas fa-stop me-1"></span>End now</button>
                </form>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))<div class="alert alert-subtle-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <x-admin.card title="Settings">
                <form action="{{ route('admin.campaigns.update', $campaign) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <span class="badge badge-phoenix badge-phoenix-{{ $tone }}">{{ ucfirst($campaign->status) }}</span>
                        <span class="text-body-tertiary fs-10">{{ $campaign->memberCount() }} members</span>
                    </div>
                    <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name', $campaign->name) }}" required></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Starts</label><input class="form-control" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($campaign->starts_at)->format('Y-m-d\TH:i')) }}"></div>
                        <div class="col-6"><label class="form-label">Ends</label><input class="form-control" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($campaign->ends_at)->format('Y-m-d\TH:i')) }}"></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Accent</label><input class="form-control" name="accent_color" value="{{ old('accent_color', $campaign->accent_color) }}" placeholder="#e63757"></div>
                        <div class="col-6"><label class="form-label">Badge</label><input class="form-control" name="badge_text" value="{{ old('badge_text', $campaign->badge_text) }}" placeholder="SALE"></div>
                    </div>
                    <button class="btn btn-primary w-100">Save settings</button>
                </form>

                <hr class="my-4">
                <h6 class="text-body-tertiary text-uppercase fs-10 mb-2">Performance</h6>
                <div class="row g-2 text-center mb-1">
                    <div class="col-4"><div class="fw-bold fs-6">{{ number_format($analytics['impressions']) }}</div><div class="fs-10 text-body-tertiary">Views</div></div>
                    <div class="col-4"><div class="fw-bold fs-6">{{ number_format($analytics['clicks']) }}</div><div class="fs-10 text-body-tertiary">Clicks</div></div>
                    <div class="col-4"><div class="fw-bold fs-6 {{ $analytics['ctr'] >= 5 ? 'text-success' : '' }}">{{ $analytics['ctr'] }}%</div><div class="fs-10 text-body-tertiary">CTR</div></div>
                </div>
                <p class="fs-10 text-body-tertiary mb-0">Across this campaign's homepage sections.</p>

                @if ($campaign->page)
                    <hr class="my-4">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.merchandising.index', ['page' => $campaign->page->slug]) }}" class="btn btn-phoenix-primary btn-sm"><span class="fas fa-table-cells me-1"></span>Build landing page</a>
                        @if ($campaign->page->isPublished())
                            <a href="{{ $campaign->page->url() }}" target="_blank" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-external-link me-1"></span>View /p/{{ $campaign->page->slug }}</a>
                        @endif
                    </div>
                @endif
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-8">
            @php($groups = [
                ['type' => 'section', 'label' => 'Sections', 'items' => $campaign->sections, 'display' => fn ($m) => $m->title, 'options' => $candidates['section'], 'optLabel' => fn ($o) => $o->title],
                ['type' => 'banner', 'label' => 'Banners', 'items' => $campaign->banners, 'display' => fn ($m) => $m->title, 'options' => $candidates['banner'], 'optLabel' => fn ($o) => $o->title],
                ['type' => 'coupon', 'label' => 'Coupons', 'items' => $campaign->coupons, 'display' => fn ($m) => $m->code, 'options' => $candidates['coupon'], 'optLabel' => fn ($o) => $o->code],
                ['type' => 'promo', 'label' => 'Promotional prices', 'items' => $campaign->promotionalPrices, 'display' => fn ($m) => $m->label ?? ('#'.$m->id), 'options' => $candidates['promo'], 'optLabel' => fn ($o) => $o->label ?? ('#'.$o->id)],
            ])

            @foreach ($groups as $g)
                <x-admin.card :title="$g['label']" class="mb-4" flush>
                    <ul class="list-group list-group-flush">
                        @forelse ($g['items'] as $item)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $g['display']($item) }}</span>
                                <form action="{{ route('admin.campaigns.detach', $campaign) }}" method="POST">@csrf
                                    <input type="hidden" name="member_type" value="{{ $g['type'] }}">
                                    <input type="hidden" name="member_id" value="{{ $item->id }}">
                                    <button class="btn btn-sm btn-phoenix-danger" title="Remove"><span class="fas fa-xmark"></span></button>
                                </form>
                            </li>
                        @empty
                            <li class="list-group-item text-body-tertiary fs-10">None yet.</li>
                        @endforelse
                    </ul>
                    @if ($g['options']->isNotEmpty())
                        <div class="p-3 border-top border-translucent">
                            <form action="{{ route('admin.campaigns.attach', $campaign) }}" method="POST" class="d-flex gap-2">@csrf
                                <input type="hidden" name="member_type" value="{{ $g['type'] }}">
                                <select name="member_id" class="form-select form-select-sm">
                                    @foreach ($g['options'] as $o)<option value="{{ $o->id }}">{{ $g['optLabel']($o) }}</option>@endforeach
                                </select>
                                <button class="btn btn-sm btn-phoenix-primary text-nowrap"><span class="fas fa-plus me-1"></span>Add</button>
                            </form>
                        </div>
                    @endif
                </x-admin.card>
            @endforeach
        </div>
    </div>
@endsection
