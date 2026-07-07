@extends('admin.layouts.app')

@section('title', 'Banners — gobuy admin')
@section('page-title', 'Banners')

@use('App\Modules\Marketing\Models\Banner')

@section('content')
    <x-admin.page-header title="Banner management" subtitle="Manage homepage hero banners and promotional strips.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary btn-sm" id="btnNewBanner">
                <span class="fas fa-plus me-1"></span>New banner
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- ── Status Summary Cards ─────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="border border-translucent rounded-3 p-3 bg-body h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;"></span>
                    <p class="fs-10 text-body-tertiary mb-0 text-uppercase fw-bold ls-1">Live</p>
                </div>
                <h3 class="mb-0 text-body-emphasis fw-bold">{{ $summary['live'] }}</h3>
                <p class="fs-10 text-body-tertiary mb-0 mt-1">Currently displaying</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border border-translucent rounded-3 p-3 bg-body h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;"></span>
                    <p class="fs-10 text-body-tertiary mb-0 text-uppercase fw-bold ls-1">Scheduled</p>
                </div>
                <h3 class="mb-0 text-body-emphasis fw-bold">{{ $summary['scheduled'] }}</h3>
                <p class="fs-10 text-body-tertiary mb-0 mt-1">Upcoming activations</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border border-translucent rounded-3 p-3 bg-body h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-secondary" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;"></span>
                    <p class="fs-10 text-body-tertiary mb-0 text-uppercase fw-bold ls-1">Draft</p>
                </div>
                <h3 class="mb-0 text-body-emphasis fw-bold">{{ $summary['draft'] }}</h3>
                <p class="fs-10 text-body-tertiary mb-0 mt-1">Hidden / inactive</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border border-translucent rounded-3 p-3 bg-body h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-danger" style="width: 8px; height: 8px; border-radius: 50%; padding: 0;"></span>
                    <p class="fs-10 text-body-tertiary mb-0 text-uppercase fw-bold ls-1">Expired</p>
                </div>
                <h3 class="mb-0 text-body-emphasis fw-bold">{{ $summary['expired'] }}</h3>
                <p class="fs-10 text-body-tertiary mb-0 mt-1">Past end date</p>
            </div>
        </div>
    </div>

    {{-- ── Banner Listing Table ─────────────────────────────────────────────────── --}}
    <x-admin.card title="All banners" subtitle="Sorted by placement and display order." flush>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 80px;">Preview</th>
                        <th>Banner</th>
                        <th>Placement</th>
                        <th>Status</th>
                        <th>Schedule</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($banners as $banner)
                        @php
                            $now = now();
                            $timelineLabel = null;
                            $timelineClass = 'text-body-tertiary';

                            if ($banner->isLive()) {
                                if ($banner->ends_at) {
                                    $daysLeft = (int) $now->diffInDays($banner->ends_at, false);
                                    if ($daysLeft <= 0) {
                                        $timelineLabel = 'Expired';
                                        $timelineClass = 'text-danger';
                                    } elseif ($daysLeft === 1) {
                                        $timelineLabel = 'Expires tomorrow';
                                        $timelineClass = 'text-warning';
                                    } elseif ($daysLeft <= 7) {
                                        $timelineLabel = "Expires in {$daysLeft}d";
                                        $timelineClass = 'text-warning';
                                    } else {
                                        $timelineLabel = 'Live';
                                        $timelineClass = 'text-success';
                                    }
                                } else {
                                    $timelineLabel = 'Live';
                                    $timelineClass = 'text-success';
                                }
                            } elseif ($banner->is_active && $banner->starts_at && $banner->starts_at->gt($now)) {
                                $daysUntil = (int) $now->diffInDays($banner->starts_at, false);
                                $timelineLabel = $daysUntil <= 1 ? 'Starts tomorrow' : "Starts in {$daysUntil}d";
                                $timelineClass = 'text-primary';
                            } elseif ($banner->ends_at && $banner->ends_at->lt($now)) {
                                $daysAgo = (int) $banner->ends_at->diffInDays($now);
                                $timelineLabel = $daysAgo <= 1 ? 'Expired yesterday' : "Expired {$daysAgo}d ago";
                                $timelineClass = 'text-danger';
                            } else {
                                $timelineLabel = 'Draft';
                                $timelineClass = 'text-body-tertiary';
                            }
                        @endphp
                        <tr>
                            <td>
                                <div class="rounded-2 overflow-hidden flex-shrink-0" style="width:72px;height:40px;background:{{ $banner->imageUrl() ? 'center/cover no-repeat url('.$banner->imageUrl().')' : $banner->gradient() }};"></div>
                            </td>
                            <td>
                                <p class="fw-semibold text-body-emphasis mb-0 fs-9">{{ $banner->title }}</p>
                                @if ($banner->subtitle)
                                    <p class="fs-10 text-body-tertiary mb-0 text-truncate" style="max-width: 220px;">{{ $banner->subtitle }}</p>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-phoenix badge-phoenix-secondary fs-10">{{ str_replace('_', ' ', $banner->placement) }}</span>
                                <span class="ms-1 text-body-tertiary fs-10">{{ $banner->layout }}</span>
                            </td>
                            <td>
                                @if ($banner->isLive())
                                    <span class="badge badge-phoenix badge-phoenix-success">Live</span>
                                @elseif ($banner->is_active && $banner->starts_at && $banner->starts_at->gt(now()))
                                    <span class="badge badge-phoenix badge-phoenix-primary">Scheduled</span>
                                @elseif ($banner->ends_at && $banner->ends_at->lt(now()))
                                    <span class="badge badge-phoenix badge-phoenix-danger">Expired</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-secondary">Draft</span>
                                @endif
                            </td>
                            <td>
                                <p class="fs-10 mb-0 {{ $timelineClass }} fw-semibold">{{ $timelineLabel }}</p>
                                @if ($banner->starts_at || $banner->ends_at)
                                    <p class="fs-10 text-body-tertiary mb-0">
                                        {{ optional($banner->starts_at)->format('M j') ?? '—' }}
                                        →
                                        {{ optional($banner->ends_at)->format('M j') ?? '∞' }}
                                    </p>
                                @else
                                    <p class="fs-10 text-body-tertiary mb-0">No schedule</p>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions justify-content-end">
                                    <button type="button" class="btn btn-sm btn-phoenix-secondary js-edit-banner"
                                        data-banner-id="{{ $banner->id }}"
                                        data-banner="{{ json_encode([
                                            'title'           => $banner->title,
                                            'subtitle'        => $banner->subtitle,
                                            'cta_label'       => $banner->cta_label,
                                            'cta_variant'     => $banner->cta_variant,
                                            'link_url'        => $banner->link_url,
                                            'cta_link'        => $banner->cta_link,
                                            'placement'       => $banner->placement,
                                            'layout'          => $banner->layout,
                                            'theme'           => $banner->theme,
                                            'text_theme'      => $banner->text_theme,
                                            'overlay_opacity' => $banner->overlay_opacity,
                                            'focal_point'     => $banner->focal_point ?? 'center',
                                            'height'          => $banner->height ?? 'md',
                                            'content_position' => $banner->content_position ?? 'start',
                                            'title_size'      => $banner->title_size ?? 'md',
                                            'cta_size'        => $banner->cta_size ?? 'md',
                                            'cta_radius'      => $banner->cta_radius ?? 'pill',
                                            'ribbon'          => $banner->ribbon,
                                            'countdown_to'    => optional($banner->countdown_to)->format('Y-m-d\TH:i'),
                                            'sort_order'      => $banner->sort_order,
                                            'is_active'       => $banner->is_active ? 1 : 0,
                                            'starts_at'       => optional($banner->starts_at)->format('Y-m-d\TH:i'),
                                            'ends_at'         => optional($banner->ends_at)->format('Y-m-d\TH:i'),
                                            'image_url'       => $banner->imageUrl(),
                                        ]) }}">
                                        <span class="fas fa-pen"></span>
                                    </button>
                                    @if ($banner->destinationUrl())
                                        <x-admin.promote-button :url="$banner->destinationUrl()" :name="$banner->title" :image="$banner->imageUrl()" :campaign="$banner->title" />
                                    @endif
                                    @if ($banner->hasBrokenLink())
                                        <span class="badge badge-phoenix badge-phoenix-warning" title="This banner's link target is no longer available"><span class="fas fa-link-slash me-1"></span>Broken link</span>
                                    @endif
                                    <form action="{{ route('admin.banners.update', $banner) }}" method="POST" class="d-inline">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="title" value="{{ $banner->title }}">
                                        <input type="hidden" name="placement" value="{{ $banner->placement }}">
                                        <input type="hidden" name="layout" value="{{ $banner->layout }}">
                                        <input type="hidden" name="theme" value="{{ $banner->theme }}">
                                        <input type="hidden" name="text_theme" value="{{ $banner->text_theme }}">
                                        <input type="hidden" name="is_active" value="{{ $banner->is_active ? 0 : 1 }}">
                                        <button class="btn btn-sm btn-phoenix-secondary" title="{{ $banner->is_active ? 'Deactivate' : 'Activate' }}">
                                            <span class="fas {{ $banner->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></span>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-phoenix-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#actionModal"
                                        data-action="{{ route('admin.banners.destroy', $banner) }}"
                                        data-method="DELETE"
                                        data-title="Delete Banner"
                                        data-message="Are you sure you want to delete &ldquo;{{ $banner->title }}&rdquo;? This action cannot be undone."
                                        data-confirm-text="Yes, delete it"
                                        data-variant="danger">
                                        <span class="fas fa-trash"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-admin.empty-state icon="fa-image" text="No banners yet. Create your first banner using the button above." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    {{-- ═══════════════════════════════════════════════════════════════════════════
         SLIDE-OVER PANEL — Create / Edit Banner
         Uses Bootstrap offcanvas (right edge), matching modern CMS patterns.
    ══════════════════════════════════════════════════════════════════════════════ --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="bannerOffcanvas" style="width: min(600px, 100vw);" aria-labelledby="bannerOffcanvasLabel">
        <div class="offcanvas-header border-bottom border-translucent">
            <div>
                <h5 class="offcanvas-title mb-0" id="bannerOffcanvasLabel">New banner</h5>
                <p class="fs-10 text-body-tertiary mb-0" id="bannerOffcanvasSubtitle">Preview updates live as you fill in the form.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        {{-- Live preview strip --}}
        <div class="px-4 pt-3 pb-0">
            <div class="mb-3">
                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-phoenix-secondary active js-preview-device" data-device="desktop" style="font-size: 0.72rem;">
                        <span class="fas fa-desktop me-1"></span>Desktop
                    </button>
                    <button type="button" class="btn btn-sm btn-phoenix-secondary js-preview-device" data-device="tablet" style="font-size: 0.72rem;">
                        <span class="fas fa-tablet-screen-button me-1"></span>Tablet
                    </button>
                    <button type="button" class="btn btn-sm btn-phoenix-secondary js-preview-device" data-device="mobile" style="font-size: 0.72rem;">
                        <span class="fas fa-mobile-screen me-1"></span>Mobile
                    </button>
                </div>
                <div class="js-preview-container gb-banner rounded-3 overflow-hidden position-relative d-flex align-items-center transition-all"
                     id="bannerPreview"
                     style="min-height: 180px; transition: all 0.25s ease;">
                    <span id="bp-ribbon" class="gb-banner__ribbon" style="display:none;"></span>
                    <div class="p-4 position-relative" id="bp-content" style="max-width:62%;">
                        <h3 id="bp-title" class="fw-bolder mb-1 text-white">Your headline</h3>
                        <p id="bp-subtitle" class="text-white-50 mb-3">Supporting copy</p>
                        <a id="bp-cta" class="btn btn-light btn-sm rounded-pill" href="#!">Shop now</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="offcanvas-body pt-0">
            <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" id="bannerForm">
                @csrf
                <input type="hidden" name="_banner_id" id="f-banner-id" value="">

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input class="form-control" name="title" id="f-title" required placeholder="Bold headline that grabs attention">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Subtitle</label>
                        <input class="form-control" name="subtitle" id="f-subtitle" placeholder="Supporting copy (optional)">
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-sm-5">
                        <label class="form-label">CTA label</label>
                        <input class="form-control" name="cta_label" id="f-cta" placeholder="Shop now">
                    </div>
                    <div class="col-12 col-sm-7">
                        <x-admin.link-picker name="link" label="Button links to" />
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label">Layout</label>
                        <select class="form-select" name="layout" id="f-layout">
                            @foreach (Banner::LAYOUTS as $l)<option value="{{ $l }}">{{ ucfirst($l) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Placement</label>
                        <select class="form-select" name="placement" id="f-placement">
                            <option value="home_hero">Home hero</option>
                            <option value="home_strip">Home strip</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Sort order</label>
                        <input class="form-control" type="number" name="sort_order" id="f-sort" value="0" min="0">
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label">Colour theme</label>
                        <select class="form-select" name="theme" id="f-theme">
                            @foreach (array_keys(Banner::THEMES) as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Text colour</label>
                        <select class="form-select" name="text_theme" id="f-text-theme">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">CTA style</label>
                        <select class="form-select" name="cta_variant" id="f-cta-variant">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                            <option value="primary">Primary</option>
                            <option value="outline">Outline</option>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label">Banner height</label>
                        <select class="form-select" name="height" id="f-height">
                            <option value="sm">Short</option><option value="md" selected>Medium</option><option value="lg">Tall</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Content position</label>
                        <select class="form-select" name="content_position" id="f-position">
                            <option value="start" selected>Left</option><option value="center">Center</option><option value="end">Right</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Title size</label>
                        <select class="form-select" name="title_size" id="f-title-size">
                            <option value="sm">Small</option><option value="md" selected>Medium</option><option value="lg">Large</option>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="form-label">CTA size</label>
                        <select class="form-select" name="cta_size" id="f-cta-size">
                            <option value="sm">Small</option><option value="md" selected>Medium</option><option value="lg">Large</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">CTA shape</label>
                        <select class="form-select" name="cta_radius" id="f-cta-radius">
                            <option value="pill" selected>Pill</option><option value="rounded">Rounded</option><option value="square">Square</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label">Ribbon <span class="text-body-tertiary fs-10">(optional)</span></label>
                        <input class="form-control" name="ribbon" id="f-ribbon" maxlength="24" placeholder="-40%">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Image overlay <span class="text-body-tertiary fs-10">(<span id="f-overlay-val">35</span>%)</span></label>
                    <input type="range" class="form-range" name="overlay_opacity" id="f-overlay" min="0" max="100" value="35">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Desktop image</label>
                        <input class="form-control" type="file" name="image" id="f-image" accept="image/*">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Mobile image <span class="text-body-tertiary fs-10">(optional)</span></label>
                        <input class="form-control" type="file" name="mobile_image" accept="image/*">
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Focal point</label>
                        <select class="form-select" name="focal_point" id="f-focal">
                            <option value="center">Center</option><option value="top">Top</option><option value="bottom">Bottom</option>
                            <option value="left">Left</option><option value="right">Right</option><option value="top right">Top right</option>
                        </select>
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <div class="form-check form-switch mb-0 pb-1">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="f-active" checked>
                            <label class="form-check-label fw-semibold" for="f-active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Start date</label>
                        <input class="form-control" type="datetime-local" name="starts_at" id="f-starts">
                    </div>
                    <div class="col-6">
                        <label class="form-label">End date</label>
                        <input class="form-control" type="datetime-local" name="ends_at" id="f-ends">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Countdown timer <span class="text-body-tertiary fs-10">(optional — shows a live countdown on the banner)</span></label>
                    <input class="form-control" type="datetime-local" name="countdown_to" id="f-countdown">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1" id="bannerSubmitBtn">Create banner</button>
                    <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
        (function () {
            var themes = @json(Banner::THEMES);
            var preview = document.getElementById('bannerPreview');
            var bpTitle = document.getElementById('bp-title');
            var bpSubtitle = document.getElementById('bp-subtitle');
            var bpCta = document.getElementById('bp-cta');
            var uploadedUrl = null;

            var f = {
                bannerId:    document.getElementById('f-banner-id'),
                title:       document.getElementById('f-title'),
                subtitle:    document.getElementById('f-subtitle'),
                cta:         document.getElementById('f-cta'),
                layout:      document.getElementById('f-layout'),
                placement:   document.getElementById('f-placement'),
                sort:        document.getElementById('f-sort'),
                theme:       document.getElementById('f-theme'),
                textTheme:   document.getElementById('f-text-theme'),
                ctaVariant:  document.getElementById('f-cta-variant'),
                overlay:     document.getElementById('f-overlay'),
                image:       document.getElementById('f-image'),
                active:      document.getElementById('f-active'),
                focal:       document.getElementById('f-focal'),
                starts:      document.getElementById('f-starts'),
                ends:        document.getElementById('f-ends'),
                height:      document.getElementById('f-height'),
                position:    document.getElementById('f-position'),
                titleSize:   document.getElementById('f-title-size'),
                ctaSize:     document.getElementById('f-cta-size'),
                ctaRadius:   document.getElementById('f-cta-radius'),
                ribbon:      document.getElementById('f-ribbon'),
                countdown:   document.getElementById('f-countdown'),
            };
            var bpRibbon  = document.getElementById('bp-ribbon');
            var bpContent = document.getElementById('bp-content');

            var form      = document.getElementById('bannerForm');
            var submitBtn = document.getElementById('bannerSubmitBtn');
            var canvasLabel    = document.getElementById('bannerOffcanvasLabel');
            var canvasSubtitle = document.getElementById('bannerOffcanvasSubtitle');

            // Device preview widths
            var deviceWidths = { desktop: '100%', tablet: '420px', mobile: '280px' };

            document.querySelectorAll('.js-preview-device').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.js-preview-device').forEach(function (b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                    var device = btn.getAttribute('data-device');
                    preview.style.maxWidth = deviceWidths[device];
                    preview.style.margin = device === 'desktop' ? '' : '0 auto';
                });
            });

            var titleSizes = { sm: '1.3rem', md: '1.7rem', lg: '2.3rem' };
            var heights = { sm: '150px', md: '200px', lg: '260px' };
            var radii = { pill: 'rounded-pill', rounded: 'rounded-3', square: 'rounded-0' };
            var ctaSizes = { sm: 'btn-sm', md: '', lg: 'btn-lg' };

            function render() {
                bpTitle.textContent = f.title.value || 'Your headline';
                bpSubtitle.textContent = f.subtitle.value || 'Supporting copy';
                bpSubtitle.style.display = f.subtitle.value ? '' : 'none';
                bpCta.textContent = f.cta.value || 'Shop now';

                var dark = f.textTheme.value === 'dark';
                bpTitle.className = 'fw-bolder mb-1 ' + (dark ? 'text-dark' : 'text-white');
                bpTitle.style.fontSize = titleSizes[f.titleSize.value] || titleSizes.md;
                bpSubtitle.className = (dark ? 'text-body-secondary' : 'text-white-50') + ' mb-3';

                var ctaMap = { light: 'btn-light', dark: 'btn-dark', primary: 'btn-primary', outline: 'btn-outline-light' };
                bpCta.className = 'btn ' + (ctaSizes[f.ctaSize.value] || '') + ' ' + (radii[f.ctaRadius.value] || 'rounded-pill') + ' ' + (ctaMap[f.ctaVariant.value] || 'btn-light');

                // Content position → flex + text alignment.
                var pos = f.position.value;
                preview.style.justifyContent = pos === 'center' ? 'center' : (pos === 'end' ? 'flex-end' : 'flex-start');
                bpContent.style.textAlign = pos === 'center' ? 'center' : (pos === 'end' ? 'right' : 'left');
                bpContent.style.maxWidth = pos === 'center' ? '90%' : '62%';

                // Ribbon.
                if (f.ribbon.value) { bpRibbon.style.display = ''; bpRibbon.textContent = f.ribbon.value; }
                else { bpRibbon.style.display = 'none'; }

                var o = (parseInt(f.overlay.value, 10) || 0) / 100;
                document.getElementById('f-overlay-val').textContent = f.overlay.value;

                if (uploadedUrl) {
                    preview.style.background = 'linear-gradient(90deg, rgba(0,0,0,' + o + ') 0%, rgba(0,0,0,0) 100%), center/cover no-repeat url(' + uploadedUrl + ')';
                } else {
                    preview.style.background = themes[f.theme.value] || themes.indigo;
                }
                preview.style.minHeight = heights[f.height.value] || heights.md;
            }

            Object.values(f).forEach(function (el) {
                if (el) { el.addEventListener('input', render); el.addEventListener('change', render); }
            });

            f.image && f.image.addEventListener('change', function () {
                var file = this.files && this.files[0];
                uploadedUrl = file ? URL.createObjectURL(file) : null;
                render();
            });

            // ── Open for NEW ──
            document.getElementById('btnNewBanner').addEventListener('click', function () {
                form.action = '{{ route('admin.banners.store') }}';
                form.querySelector('[name="_method"]') && form.querySelector('[name="_method"]').remove();
                f.bannerId.value = '';
                form.reset();
                var lpNew = form.querySelector('.gb-link-picker'); if (lpNew && lpNew._gbReset) { lpNew._gbReset(); }
                uploadedUrl = null;
                canvasLabel.textContent = 'New banner';
                canvasSubtitle.textContent = 'Preview updates live as you fill in the form.';
                submitBtn.textContent = 'Create banner';
                // reset active checkbox default
                f.active.checked = true;
                render();
                new window.bootstrap.Offcanvas(document.getElementById('bannerOffcanvas')).show();
            });

            // ── Open for EDIT ──
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-edit-banner');
                if (!btn) { return; }

                var banner = JSON.parse(btn.getAttribute('data-banner'));
                var bannerId = btn.getAttribute('data-banner-id');

                // Set form to PUT
                form.action = '/admin/banners/' + bannerId;
                var existing = form.querySelector('[name="_method"]');
                if (!existing) {
                    var m = document.createElement('input');
                    m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
                    form.prepend(m);
                } else {
                    existing.value = 'PUT';
                }

                f.bannerId.value = bannerId;
                f.title.value        = banner.title || '';
                f.subtitle.value     = banner.subtitle || '';
                f.cta.value          = banner.cta_label || '';
                f.ctaVariant.value   = banner.cta_variant || 'light';
                var lpEdit = form.querySelector('.gb-link-picker'); if (lpEdit && lpEdit._gbSet) { lpEdit._gbSet(banner.cta_link || {}); }
                f.placement.value    = banner.placement || 'home_hero';
                f.layout.value       = banner.layout || 'hero';
                f.theme.value        = banner.theme || 'indigo';
                f.textTheme.value    = banner.text_theme || 'light';
                f.overlay.value      = banner.overlay_opacity || 35;
                f.focal.value        = banner.focal_point || 'center';
                f.height.value       = banner.height || 'md';
                f.position.value     = banner.content_position || 'start';
                f.titleSize.value    = banner.title_size || 'md';
                f.ctaSize.value      = banner.cta_size || 'md';
                f.ctaRadius.value    = banner.cta_radius || 'pill';
                f.ribbon.value       = banner.ribbon || '';
                f.countdown.value    = banner.countdown_to || '';
                f.sort.value         = banner.sort_order || 0;
                f.active.checked     = !!banner.is_active;
                f.starts.value       = banner.starts_at || '';
                f.ends.value         = banner.ends_at || '';

                // Use existing image in preview if available
                uploadedUrl = banner.image_url || null;

                canvasLabel.textContent = 'Edit banner';
                canvasSubtitle.textContent = 'Changes are saved immediately on submit.';
                submitBtn.textContent = 'Save changes';

                render();
                new window.bootstrap.Offcanvas(document.getElementById('bannerOffcanvas')).show();
            });

            render();
        })();
        </script>
    @endpush
@endsection
