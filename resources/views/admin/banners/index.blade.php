@extends('admin.layouts.app')

@section('title', 'Banners — gobuy admin')
@section('page-title', 'Banners')

@use('App\Modules\Marketing\Models\Banner')

@section('content')
    <x-admin.page-header title="Homepage banners" subtitle="Configurable, scheduled hero &amp; promo banners" />

    @if ($errors->any())
        <div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <x-admin.card title="Live banners" subtitle="Drag-free ordering via sort value; scheduling respected on the storefront." style="max-height: 850px;" bodyClass="overflow-y-auto scrollbar">
                @forelse ($banners as $banner)
                    <div class="d-flex gap-3 align-items-center border-bottom border-translucent py-3">
                        <div class="rounded-2 overflow-hidden flex-shrink-0" style="width:96px;height:54px;background:{{ $banner->imageUrl() ? 'center/cover no-repeat url('.$banner->imageUrl().')' : $banner->gradient() }};"></div>
                        <div class="flex-grow-1 min-w-0">
                            <p class="fw-semibold mb-0 text-truncate">{{ $banner->title }}
                                <span class="badge badge-phoenix badge-phoenix-{{ $banner->isLive() ? 'success' : 'secondary' }} ms-1">{{ $banner->isLive() ? 'Live' : ($banner->is_active ? 'Scheduled' : 'Hidden') }}</span>
                            </p>
                            <p class="fs-10 text-body-tertiary mb-0">{{ $banner->layout }} · {{ $banner->placement }}
                                @if ($banner->starts_at || $banner->ends_at) · {{ optional($banner->starts_at)->format('M j') ?? '…' }}–{{ optional($banner->ends_at)->format('M j') ?? '…' }}@endif
                            </p>
                        </div>
                        <form action="{{ route('admin.banners.update', $banner) }}" method="POST" class="d-inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="title" value="{{ $banner->title }}">
                            <input type="hidden" name="placement" value="{{ $banner->placement }}">
                            <input type="hidden" name="layout" value="{{ $banner->layout }}">
                            <input type="hidden" name="theme" value="{{ $banner->theme }}">
                            <input type="hidden" name="text_theme" value="{{ $banner->text_theme }}">
                            <input type="hidden" name="is_active" value="{{ $banner->is_active ? 0 : 1 }}">
                            <button class="btn btn-sm btn-phoenix-secondary">{{ $banner->is_active ? 'Hide' : 'Show' }}</button>
                        </form>
                        <button type="button" class="btn btn-sm text-danger" 
                            data-bs-toggle="modal" 
                            data-bs-target="#actionModal" 
                            data-action="{{ route('admin.banners.destroy', $banner) }}" 
                            data-method="DELETE" 
                            data-title="Delete Banner" 
                            data-message="Are you sure you want to delete this banner? This action cannot be undone." 
                            data-confirm-text="Yes, delete it" 
                            data-variant="danger">
                            <span class="fas fa-trash"></span>
                        </button>
                    </div>
                @empty
                    <p class="text-body-tertiary mb-0">No banners yet. Create one on the right.</p>
                @endforelse
            </x-admin.card>
        </div>

        <div class="col-12 col-xl-6">
            <x-admin.card title="New banner" subtitle="Preview updates live as you type.">
                {{-- Live preview --}}
                <div id="bannerPreview" class="rounded-3 overflow-hidden position-relative d-flex align-items-center mb-4" style="min-height:200px;">
                    <div class="p-4 position-relative">
                        <h3 id="bp-title" class="fw-bolder mb-1 text-white">Your headline</h3>
                        <p id="bp-subtitle" class="text-white-50 mb-3">Supporting copy</p>
                        <a id="bp-cta" class="btn btn-light btn-sm rounded-pill" href="#!">Shop now</a>
                    </div>
                </div>

                <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" id="bannerForm">
                    @csrf
                    <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" id="f-title" required></div>
                    <div class="mb-3"><label class="form-label">Subtitle</label><input class="form-control" name="subtitle" id="f-subtitle"></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">CTA label</label><input class="form-control" name="cta_label" id="f-cta" placeholder="Shop now"></div>
                        <div class="col-6"><label class="form-label">Link URL</label><input class="form-control" name="link_url" placeholder="/products"></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Layout</label>
                            <select class="form-select" name="layout" id="f-layout">
                                @foreach (Banner::LAYOUTS as $l)<option value="{{ $l }}">{{ ucfirst($l) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Placement</label>
                            <select class="form-select" name="placement">
                                <option value="home_hero">Home hero</option>
                                <option value="home_strip">Home strip</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Colour theme</label>
                            <select class="form-select" name="theme" id="f-theme">
                                @foreach (array_keys(Banner::THEMES) as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Text colour</label>
                            <select class="form-select" name="text_theme" id="f-text-theme">
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">CTA style</label>
                            <select class="form-select" name="cta_variant" id="f-cta-variant">
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                                <option value="primary">Primary</option>
                                <option value="outline">Outline</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Image overlay <span class="text-body-tertiary fs-10">(<span id="f-overlay-val">35</span>%)</span></label>
                            <input type="range" class="form-range mt-2" name="overlay_opacity" id="f-overlay" min="0" max="100" value="35">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Desktop image</label><input class="form-control" type="file" name="image" id="f-image" accept="image/*"></div>
                        <div class="col-6"><label class="form-label">Mobile image <span class="text-body-tertiary fs-10">(optional)</span></label><input class="form-control" type="file" name="mobile_image" accept="image/*"></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Focal point</label>
                            <select class="form-select" name="focal_point">
                                <option value="center">Center</option><option value="top">Top</option><option value="bottom">Bottom</option>
                                <option value="left">Left</option><option value="right">Right</option><option value="top right">Top right</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Sort order</label><input class="form-control" type="number" name="sort_order" value="0" min="0"></div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Starts</label><input class="form-control" type="datetime-local" name="starts_at"></div>
                        <div class="col-6"><label class="form-label">Ends</label><input class="form-control" type="datetime-local" name="ends_at"></div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="bannerActive" checked>
                        <label class="form-check-label" for="bannerActive">Active</label>
                    </div>
                    <button class="btn btn-primary">Create banner</button>
                </form>
            </x-admin.card>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var themes = @json(Banner::THEMES);
                var preview = document.getElementById('bannerPreview');
                var title = document.getElementById('bp-title');
                var subtitle = document.getElementById('bp-subtitle');
                var cta = document.getElementById('bp-cta');
                var uploadedUrl = null;

                var f = {
                    title: document.getElementById('f-title'),
                    subtitle: document.getElementById('f-subtitle'),
                    cta: document.getElementById('f-cta'),
                    layout: document.getElementById('f-layout'),
                    theme: document.getElementById('f-theme'),
                    textTheme: document.getElementById('f-text-theme'),
                    ctaVariant: document.getElementById('f-cta-variant'),
                    overlay: document.getElementById('f-overlay'),
                    image: document.getElementById('f-image'),
                };

                function render() {
                    title.textContent = f.title.value || 'Your headline';
                    subtitle.textContent = f.subtitle.value || 'Supporting copy';
                    subtitle.style.display = f.subtitle.value ? '' : 'none';
                    cta.textContent = f.cta.value || 'Shop now';

                    var dark = f.textTheme.value === 'dark';
                    title.className = 'fw-bolder mb-1 ' + (dark ? 'text-dark' : 'text-white');
                    subtitle.className = (dark ? 'text-body-secondary' : 'text-white-50') + ' mb-3';
                    cta.className = 'btn btn-sm rounded-pill ' + ({ light: 'btn-light', dark: 'btn-dark', primary: 'btn-primary', outline: 'btn-outline-light' }[f.ctaVariant.value] || 'btn-light');

                    var o = (parseInt(f.overlay.value, 10) || 0) / 100;
                    document.getElementById('f-overlay-val').textContent = f.overlay.value;
                    if (uploadedUrl) {
                        preview.style.background = 'linear-gradient(90deg, rgba(0,0,0,' + o + ') 0%, rgba(0,0,0,0) 100%), center/cover no-repeat url(' + uploadedUrl + ')';
                    } else {
                        preview.style.background = themes[f.theme.value] || themes.indigo;
                    }
                    preview.style.minHeight = f.layout.value === 'grid' ? '150px' : '200px';
                }

                Object.values(f).forEach(function (el) { if (el) { el.addEventListener('input', render); el.addEventListener('change', render); } });
                if (f.image) {
                    f.image.addEventListener('change', function () {
                        var file = this.files && this.files[0];
                        uploadedUrl = file ? URL.createObjectURL(file) : null;
                        render();
                    });
                }
                render();
            })();
        </script>
    @endpush
@endsection
