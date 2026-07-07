@extends('admin.layouts.app')

@section('title', 'Campaigns — gobuy admin')
@section('page-title', 'Campaigns')

@section('content')
    <x-admin.page-header title="Campaigns" subtitle="Coordinate a landing page, banners, sections, coupons & promo prices under one schedule.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary btn-sm" id="btnNewCampaign"><span class="fas fa-plus me-1"></span>New campaign</button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- Start from a template --}}
    <x-admin.card title="Start from a template" subtitle="Scaffold a ready-to-edit campaign with a landing page and pre-wired draft sections.">
        <form action="{{ route('admin.campaigns.from-template') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-12 col-md-4">
                <label class="form-label">Template</label>
                <select name="template" class="form-select">
                    @foreach ($templates as $key => $tpl)<option value="{{ $key }}">{{ $tpl['label'] }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label">Campaign name</label>
                <input name="name" class="form-control" required placeholder="e.g. Black Friday 2026">
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-phoenix-primary w-100"><span class="fas fa-wand-magic-sparkles me-1"></span>Scaffold</button>
            </div>
        </form>
    </x-admin.card>

    <x-admin.card title="All campaigns" flush class="mt-4">
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead><tr><th>Campaign</th><th>Schedule</th><th class="text-center">Members</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        @php($tone = match ($campaign->status) { 'live' => 'success', 'scheduled' => 'primary', 'ended' => 'secondary', default => 'warning' })
                        <tr>
                            <td><a href="{{ route('admin.campaigns.show', $campaign) }}" class="fw-semibold text-body-emphasis text-decoration-none">{{ $campaign->name }}</a>
                                @if ($campaign->page)<br><span class="fs-10 text-body-tertiary">/p/{{ $campaign->page->slug }}</span>@endif
                            </td>
                            <td class="fs-10 text-body-tertiary">{{ optional($campaign->starts_at)->format('M j') ?? '—' }} → {{ optional($campaign->ends_at)->format('M j') ?? '∞' }}</td>
                            <td class="text-center">{{ $campaign->memberCount() }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $tone }}">{{ ucfirst($campaign->status) }}</span></td>
                            <td class="text-end">
                                <div class="table-actions justify-content-end">
                                    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn btn-sm btn-phoenix-primary">Open</a>
                                    @if (! $campaign->isLive())
                                        <form action="{{ route('admin.campaigns.launch', $campaign) }}" method="POST" class="d-inline">@csrf
                                            <button class="btn btn-sm btn-success" title="Launch"><span class="fas fa-rocket"></span></button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.campaigns.end', $campaign) }}" method="POST" class="d-inline">@csrf
                                            <button class="btn btn-sm btn-phoenix-danger" title="End now"><span class="fas fa-stop"></span></button>
                                        </form>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-phoenix-danger" data-bs-toggle="modal" data-bs-target="#actionModal"
                                        data-action="{{ route('admin.campaigns.destroy', $campaign) }}" data-method="DELETE"
                                        data-title="Delete campaign" data-message="Delete “{{ $campaign->name }}”? Members are unlinked but kept." data-confirm-text="Yes, delete" data-variant="danger"><span class="fas fa-trash"></span></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty-state icon="fa-bullhorn" text="No campaigns yet. Start from a template above, or create one." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="campaignOffcanvas" style="width:min(480px,100vw);">
        <div class="offcanvas-header border-bottom border-translucent">
            <h5 class="offcanvas-title mb-0">New campaign</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('admin.campaigns.store') }}" method="POST">
                @csrf
                <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" required placeholder="e.g. Independence Day Sale"></div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="form-label">Starts</label><input class="form-control" type="datetime-local" name="starts_at"></div>
                    <div class="col-6"><label class="form-label">Ends</label><input class="form-control" type="datetime-local" name="ends_at"></div>
                </div>
                <div class="row g-2 mb-4">
                    <div class="col-6"><label class="form-label">Accent colour</label><input class="form-control" type="text" name="accent_color" placeholder="#e63757"></div>
                    <div class="col-6"><label class="form-label">Badge text</label><input class="form-control" name="badge_text" placeholder="SALE"></div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Create campaign</button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('btnNewCampaign').addEventListener('click', function () {
                new window.bootstrap.Offcanvas(document.getElementById('campaignOffcanvas')).show();
            });
        </script>
    @endpush
@endsection
