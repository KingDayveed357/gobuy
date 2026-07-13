@extends('admin.layouts.app')

@use('App\Modules\Marketing\Enums\SectionType')
@use('App\Modules\Marketing\Models\Banner')

@section('title', 'Page content — Quintessential Mart admin')
@section('page-title', 'Page content')

@section('content')
    <x-admin.page-header :title="$page->isHome() ? 'Homepage content' : $page->title.' — content'" :subtitle="$page->isHome() ? 'Compose, order and schedule what shoppers see on the homepage — no developer required.' : 'Building the /p/'.$page->slug.' landing page.'">
        <x-slot:actions>
            <a href="{{ route('admin.pages.index') }}" class="btn btn-phoenix-secondary btn-sm me-2"><span class="fas fa-chevron-left me-1"></span>Pages</a>
            <a href="{{ $previewUrl }}" target="_blank" class="btn btn-phoenix-info btn-sm me-2"><span class="fas fa-eye me-1"></span>Preview</a>
            @if ($draftCount > 0)
                <form action="{{ route('admin.merchandising.publish') }}" method="POST" class="d-inline me-2">
                    @csrf
                    <input type="hidden" name="page" value="{{ $page->slug }}">
                    <button type="submit" class="btn btn-success btn-sm"><span class="fas fa-rocket me-1"></span>Publish {{ $draftCount }} draft{{ $draftCount === 1 ? '' : 's' }}</button>
                </form>
            @endif
            <button type="button" class="btn btn-primary btn-sm" id="btnNewSection"><span class="fas fa-plus me-1"></span>New section</button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($draftCount > 0)
        <div class="alert alert-subtle-warning d-flex align-items-center gap-2">
            <span class="fas fa-pen-ruler"></span>
            You have <strong>{{ $draftCount }}</strong> unpublished draft section{{ $draftCount === 1 ? '' : 's' }}. They appear in <strong>Preview</strong> but not on the live homepage until published.
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @if ($sections->isEmpty() && $page->isHome())
        <div class="alert alert-subtle-info">
            No sections configured yet — the storefront is showing a sensible <strong>default homepage</strong> (categories, hero banners, top deals, new arrivals). Add a section to start customising.
        </div>
    @elseif ($sections->isEmpty())
        <div class="alert alert-subtle-info">
            This landing page has no sections yet. Add a section, then <strong>Publish</strong> to make it live at <code>/p/{{ $page->slug }}</code>.
        </div>
    @endif

    @if ($sections->isNotEmpty())
        <p class="fs-9 text-body-tertiary mb-2"><span class="fas fa-up-down-left-right me-1"></span>Drag the handle to reorder how sections stack on the storefront — the order saves automatically.</p>
    @endif

    <div id="sectionCanvas" class="gb-canvas" data-reorder-url="{{ route('admin.merchandising.reorder') }}" data-page="{{ $page->slug }}">
        @forelse ($sections as $section)
            @php($items = $previews[$section->id] ?? collect())
            <div class="gb-canvas-card card border border-translucent mb-2" data-id="{{ $section->id }}">
                <div class="card-body p-2 p-md-3 d-flex align-items-center gap-2 gap-md-3">
                    <span class="gb-drag-handle" title="Drag to reorder"><span class="fas fa-grip-vertical"></span></span>

                    <div class="flex-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge badge-phoenix badge-phoenix-secondary fs-10">{{ $section->type->label() }}</span>
                            <span class="fw-semibold text-body-emphasis fs-9">{{ $section->title ?: '(untitled)' }}</span>
                            @if ($section->isDraft())
                                <span class="badge badge-phoenix badge-phoenix-warning fs-10">Draft</span>
                            @elseif ($section->isLive())
                                <span class="badge badge-phoenix badge-phoenix-success fs-10">Live</span>
                            @elseif ($section->is_active && $section->starts_at && $section->starts_at->isFuture())
                                <span class="badge badge-phoenix badge-phoenix-primary fs-10">Scheduled</span>
                            @elseif ($section->ends_at && $section->ends_at->isPast())
                                <span class="badge badge-phoenix badge-phoenix-danger fs-10">Expired</span>
                            @else
                                <span class="badge badge-phoenix badge-phoenix-secondary fs-10">Hidden</span>
                            @endif
                            @if ($section->hasBrokenLink())
                                <span class="badge badge-phoenix badge-phoenix-warning fs-10" title="The &quot;See all&quot; link target is no longer available"><span class="fas fa-link-slash"></span> Broken link</span>
                            @endif
                            @php($sectionProblems = $problems[$section->id] ?? [])
                            @if ($sectionProblems !== [])
                                <span class="badge badge-phoenix badge-phoenix-danger fs-10" title="{{ implode(' ', $sectionProblems) }}"><span class="fas fa-triangle-exclamation me-1"></span>Incomplete</span>
                            @endif
                        </div>

                        @if ($sectionProblems !== [])
                            <div class="alert alert-subtle-warning py-1 px-2 mb-2 fs-10 d-flex align-items-start gap-2">
                                <span class="fas fa-circle-info mt-1"></span>
                                <span>{{ $sectionProblems[0] }}@if (count($sectionProblems) > 1) <span class="text-body-tertiary">(+{{ count($sectionProblems) - 1 }} more)</span>@endif</span>
                            </div>
                        @endif

                        {{-- Live mini-preview of the block's content --}}
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            @if ($section->type->isEditorial())
                                <span class="text-body-tertiary fs-10"><span class="fas fa-align-left me-1"></span>{{ \Illuminate\Support\Str::limit($section->setting('body') ?: $section->title ?: 'Editorial block', 90) }}</span>
                            @elseif ($items->isEmpty())
                                <span class="text-body-tertiary fs-10 fst-italic">No items match — this section is hidden on the storefront.</span>
                            @elseif ($section->type === SectionType::BannerRow)
                                @foreach ($items->take(4) as $b)
                                    <span class="gb-canvas-thumb" style="background:{{ $b->imageUrl() ? 'center/cover no-repeat url('.$b->imageUrl().')' : $b->gradient() }};"></span>
                                @endforeach
                            @elseif (in_array($section->type, [SectionType::ProductRail, SectionType::ProductGrid, SectionType::CountdownDeal], true))
                                @foreach ($items->take(6) as $p)
                                    <img class="gb-canvas-thumb" src="{{ $p->imageUrl() }}" alt="" loading="lazy">
                                @endforeach
                            @else
                                @foreach ($items->take(6) as $it)
                                    <span class="badge badge-phoenix badge-phoenix-info fs-10">{{ $it->name }}</span>
                                @endforeach
                            @endif
                            @if ($items->count() > 6)<span class="text-body-tertiary fs-10 ms-1">+{{ $items->count() - 6 }} more</span>@endif
                        </div>

                        {{-- Performance: impressions · clicks · CTR (last collected). --}}
                        @php($stat = $stats[$section->id] ?? null)
                        <div class="d-flex align-items-center gap-3 mt-1 fs-10 text-body-tertiary">
                            @if ($stat && $stat['impressions'] > 0)
                                <span title="Impressions"><span class="fas fa-eye me-1"></span>{{ number_format($stat['impressions']) }}</span>
                                <span title="Clicks"><span class="fas fa-hand-pointer me-1"></span>{{ number_format($stat['clicks']) }}</span>
                                <span title="Click-through rate" class="fw-semibold {{ $stat['ctr'] >= 5 ? 'text-success' : ($stat['ctr'] > 0 ? 'text-body-secondary' : '') }}">CTR {{ $stat['ctr'] }}%</span>
                            @else
                                <span class="fst-italic"><span class="fas fa-chart-simple me-1"></span>No engagement data yet</span>
                            @endif
                        </div>
                    </div>

                    <div class="table-actions flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-phoenix-secondary js-edit-section"
                            data-section-id="{{ $section->id }}"
                            data-section="{{ json_encode([
                                'type' => $section->type->value,
                                'source' => $section->source?->value,
                                'source_ref' => $section->source_ref,
                                'title' => $section->title,
                                'subtitle' => $section->subtitle,
                                'cta_label' => $section->cta_label,
                                'cta_url' => $section->cta_url,
                                'cta_link' => $section->cta_link,
                                'settings' => $section->settings,
                                'banners' => $bannerChoices[$section->id] ?? [],
                                'item_limit' => $section->item_limit,
                                'sort_order' => $section->sort_order,
                                'is_active' => $section->is_active ? 1 : 0,
                                'status' => $section->status?->value,
                                'starts_at' => optional($section->starts_at)->format('Y-m-d\TH:i'),
                                'ends_at' => optional($section->ends_at)->format('Y-m-d\TH:i'),
                            ]) }}" title="Edit"><span class="fas fa-pen"></span></button>
                        @if ($section->isDraft())
                            <form action="{{ route('admin.merchandising.update', $section) }}" method="POST" class="d-inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="type" value="{{ $section->type->value }}">
                                <input type="hidden" name="status" value="published">
                                <button class="btn btn-sm btn-phoenix-success" title="Publish this section"><span class="fas fa-rocket"></span></button>
                            </form>
                        @endif
                        <form action="{{ route('admin.merchandising.update', $section) }}" method="POST" class="d-inline">
                            @csrf @method('PUT')
                            <input type="hidden" name="type" value="{{ $section->type->value }}">
                            <input type="hidden" name="is_active" value="{{ $section->is_active ? 0 : 1 }}">
                            <button class="btn btn-sm btn-phoenix-secondary" title="{{ $section->is_active ? 'Hide' : 'Show' }}"><span class="fas {{ $section->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></span></button>
                        </form>
                        <button type="button" class="btn btn-sm btn-phoenix-danger" data-bs-toggle="modal" data-bs-target="#actionModal"
                            data-action="{{ route('admin.merchandising.destroy', $section) }}" data-method="DELETE"
                            data-title="Delete section" data-message="Remove this section?" data-confirm-text="Yes, delete" data-variant="danger" title="Delete">
                            <span class="fas fa-trash"></span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="card border border-translucent">
                <x-admin.empty-state icon="fa-table-cells-large" text="No sections yet — add one to start building this page." />
            </div>
        @endforelse
    </div>

    @push('scripts')
        <script src="{{ asset('theme/vendors/sortablejs/Sortable.min.js') }}"></script>
        <style>
            .gb-drag-handle { cursor: grab; color: var(--phoenix-tertiary-color); padding: 0 .25rem; }
            .gb-canvas-card.gb-canvas-ghost { opacity: .5; }
            .gb-canvas-thumb { width: 34px; height: 34px; border-radius: .35rem; object-fit: cover; display: inline-block; border: 1px solid var(--phoenix-border-color-translucent); }
        </style>
        <script>
            (function () {
                var canvas = document.getElementById('sectionCanvas');
                if (!canvas || !window.Sortable || !canvas.querySelector('.gb-canvas-card')) { return; }
                window.Sortable.create(canvas, {
                    handle: '.gb-drag-handle',
                    animation: 150,
                    ghostClass: 'gb-canvas-ghost',
                    onEnd: function () {
                        var ids = Array.from(canvas.querySelectorAll('.gb-canvas-card')).map(function (c) { return c.getAttribute('data-id'); });
                        fetch(canvas.getAttribute('data-reorder-url'), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            body: JSON.stringify({ ids: ids, page: canvas.getAttribute('data-page') }),
                        }).then(function (r) {
                            if (r.ok && window.Toast) { window.Toast.success('Order saved.'); }
                        });
                    },
                });
            })();
        </script>
    @endpush

    {{-- Slide-over: create / edit section --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="sectionOffcanvas" style="width:min(560px,100vw);">
        <div class="offcanvas-header border-bottom border-translucent">
            <h5 class="offcanvas-title mb-0" id="sectionOffcanvasLabel">New section</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('admin.merchandising.store') }}" method="POST" id="sectionForm">
                @csrf
                <input type="hidden" name="placement" value="{{ $page->slug }}">
                <div class="mb-3">
                    <label class="form-label">Section type</label>
                    <select class="form-select" name="type" id="s-type">
                        @foreach ($types as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12"><label class="form-label">Heading</label><input class="form-control" name="title" id="s-title" placeholder="e.g. Top deals today"></div>
                    <div class="col-12"><label class="form-label">Sub-heading</label><input class="form-control" name="subtitle" id="s-subtitle" placeholder="Optional"></div>
                </div>

                {{-- Product source (product rail/grid only) --}}
                <div class="mb-3 js-when-product">
                    <label class="form-label">Products from</label>
                    <select class="form-select" name="source" id="s-source">
                        <option value="">—</option>
                        @foreach ($sources as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
                    </select>
                </div>

                {{-- Category ref --}}
                <div class="mb-3 js-ref-category" style="display:none;">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="source_ref" id="s-ref-category">
                        @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>

                {{-- Brand ref --}}
                <div class="mb-3 js-ref-brand" style="display:none;">
                    <label class="form-label">Brand</label>
                    <select class="form-select" name="source_ref" id="s-ref-brand" disabled>
                        @foreach ($brands as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                    </select>
                </div>

                {{-- Banner row: pick the exact banners to show, in order --}}
                <div class="mb-3 js-when-bannerrow" style="display:none;">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Banners in this row</span>
                        <button type="button" class="btn btn-link btn-sm p-0 fs-10" id="s-banner-new"><span class="fas fa-plus me-1"></span>Create banner</button>
                    </label>
                    <div class="position-relative">
                        <input type="text" class="form-control form-control-sm" id="s-banner-search" placeholder="Search banners to add…" autocomplete="off">
                        <div class="list-group position-absolute w-100 shadow-sm" id="s-banner-results" style="z-index:30; display:none; max-height:230px; overflow:auto;"></div>
                    </div>
                    <div id="s-banner-chips" class="mt-2 d-flex flex-column gap-2"></div>
                    <p class="fs-10 text-body-tertiary mb-0 mt-1" id="s-banner-empty">No banners chosen yet — search above, or create one.</p>
                </div>

                {{-- Carousel toggle (banner row): rotate banners in ONE slot instead of stacking --}}
                <div class="mb-3 js-when-bannerrow" style="display:none;">
                    <div class="form-check form-switch">
                        <input type="hidden" name="settings[carousel]" value="0">
                        <input class="form-check-input" type="checkbox" name="settings[carousel]" value="1" id="s-carousel">
                        <label class="form-check-label fw-semibold" for="s-carousel">Rotate as a carousel <span class="fw-normal text-body-tertiary fs-10">(recommended for the hero — banners share one slot with autoplay instead of stacking)</span></label>
                    </div>
                </div>

                {{-- Curated collection ref (manual source) --}}
                <div class="mb-3 js-ref-collection" style="display:none;">
                    <label class="form-label">Collection</label>
                    <select class="form-select" name="source_ref" id="s-ref-collection" disabled>
                        @foreach ($collections as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                    @if ($collections->isEmpty())<p class="fs-10 text-warning mb-0 mt-1">No collections yet — create one under Collections first.</p>@endif
                </div>

                {{-- Editorial (rich text / image + copy) content --}}
                <div class="js-when-editorial" style="display:none;">
                    <div class="mb-3"><label class="form-label">Eyebrow <span class="text-body-tertiary fs-10">(small label above the heading)</span></label><input class="form-control" name="settings[eyebrow]" id="s-eyebrow" placeholder="e.g. New season"></div>
                    <div class="mb-3"><label class="form-label">Body copy</label><textarea class="form-control" name="settings[body]" id="s-body" rows="4" placeholder="Tell the story…"></textarea></div>
                    <div class="mb-3 js-media-only" style="display:none;">
                        <label class="form-label">Image</label>
                        <div class="d-flex gap-2 mb-2">
                            <input class="form-control" name="settings[image_url]" id="s-image" placeholder="https://… or upload below">
                            <button type="button" class="btn btn-phoenix-secondary btn-sm flex-shrink-0" id="s-image-clear" title="Clear image">
                                <span class="fas fa-xmark"></span>
                            </button>
                        </div>
                        {{-- File upload — uploads to /admin/media/upload and writes URL back into the text field --}}
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <label class="btn btn-sm btn-phoenix-secondary mb-0" for="s-image-file" style="cursor:pointer;">
                                <span class="fas fa-upload me-1"></span>Upload image
                            </label>
                            <input type="file" id="s-image-file" accept="image/*" class="d-none" aria-label="Upload editorial image">
                            <span id="s-image-upload-status" class="fs-10 text-body-tertiary"></span>
                        </div>
                        {{-- Live preview — updates as URL is typed or after upload --}}
                        <div id="s-image-preview-wrap" class="d-none rounded-3 overflow-hidden border border-translucent" style="max-width:320px;height:160px;background:var(--phoenix-body-highlight-bg);">
                            <img id="s-image-preview" src="" alt="Preview" style="width:100%;height:100%;object-fit:cover;display:block;">
                        </div>
                        <p class="fs-10 text-body-tertiary mt-1 mb-0">Recommended: at least 800×600px, JPG or PNG.</p>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label js-align-label">Text / media alignment</label>
                            <select class="form-select" name="settings[align]" id="s-align">
                                <option value="center">Center</option>
                                <option value="left">Left</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                        <div class="col-6 js-richtext-only" style="display:none;">
                            <label class="form-label">Band theme</label>
                            <select class="form-select" name="settings[theme]" id="s-theme">
                                <option value="default">Default</option>
                                <option value="accent">Accent (branded background)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="form-label">Item limit</label><input class="form-control" type="number" name="item_limit" id="s-limit" value="8" min="1" max="24"></div>
                    <div class="col-6"><label class="form-label">Order</label><input class="form-control" type="number" name="sort_order" id="s-sort" value="0" min="0"></div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-sm-5"><label class="form-label">"See all" label</label><input class="form-control" name="cta_label" id="s-cta-label" placeholder="View all"></div>
                    <div class="col-12 col-sm-7"><x-admin.link-picker name="link" label="&quot;See all&quot; links to" /></div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="form-label">Start date</label><input class="form-control" type="datetime-local" name="starts_at" id="s-starts"></div>
                    <div class="col-6"><label class="form-label">End date</label><input class="form-control" type="datetime-local" name="ends_at" id="s-ends"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" id="s-status">
                        <option value="draft">Draft — staged, only visible in Preview</option>
                        <option value="published">Published — live on the homepage</option>
                    </select>
                </div>

                <div class="form-check form-switch mb-4">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="s-active" checked>
                    <label class="form-check-label fw-semibold" for="s-active">Visible <span class="fw-normal text-body-tertiary fs-10">(uncheck to temporarily hide a published section)</span></label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1" id="sectionSubmitBtn">Create section</button>
                    <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
        (function () {
            var form = document.getElementById('sectionForm');
            var typeEl = document.getElementById('s-type');
            var sourceEl = document.getElementById('s-source');
            var label = document.getElementById('sectionOffcanvasLabel');
            var submitBtn = document.getElementById('sectionSubmitBtn');

            var refCategory = document.querySelector('.js-ref-category');
            var refBrand = document.querySelector('.js-ref-brand');
            var refCollection = document.querySelector('.js-ref-collection');
            var whenProduct = document.querySelectorAll('.js-when-product');
            var whenEditorial = document.querySelector('.js-when-editorial');
            var mediaOnly = document.querySelector('.js-media-only');
            var richtextOnly = document.querySelector('.js-richtext-only');
            var whenBannerRow = document.querySelectorAll('.js-when-bannerrow');

            // Only one source_ref control is active at a time so a single value posts.
            function setRef(which) {
                var map = { category: refCategory, brand: refBrand, collection: refCollection };
                Object.keys(map).forEach(function (k) {
                    var wrap = map[k];
                    var input = wrap.querySelector('select');
                    var on = (k === which);
                    wrap.style.display = on ? '' : 'none';
                    if (input) { input.disabled = !on; }
                });
            }

            function sync() {
                var type = typeEl.value;
                var isProduct = (type === 'product_rail' || type === 'product_grid' || type === 'countdown_deal');
                var isEditorial = (type === 'rich_text' || type === 'editorial_media');
                whenProduct.forEach(function (el) { el.style.display = isProduct ? '' : 'none'; });

                // Editorial (story) blocks swap the product controls for copy/media fields.
                whenEditorial.style.display = isEditorial ? '' : 'none';
                mediaOnly.style.display = (type === 'editorial_media') ? '' : 'none';
                richtextOnly.style.display = (type === 'rich_text') ? '' : 'none';
                whenBannerRow.forEach(function (el) { el.style.display = (type === 'banner_row') ? '' : 'none'; });

                if (isProduct && sourceEl.value === 'category') { setRef('category'); }
                else if (isProduct && sourceEl.value === 'brand') { setRef('brand'); }
                else if (isProduct && sourceEl.value === 'manual') { setRef('collection'); }
                else { setRef(null); }
            }

            typeEl.addEventListener('change', sync);
            sourceEl.addEventListener('change', sync);

            document.getElementById('btnNewSection').addEventListener('click', function () {
                form.action = '{{ route('admin.merchandising.store') }}';
                var m = form.querySelector('[name="_method"]'); if (m) { m.remove(); }
                form.reset();
                var lpNew = form.querySelector('.gb-link-picker'); if (lpNew && lpNew._gbReset) { lpNew._gbReset(); }
                if (window._gbBannerPicker) { window._gbBannerPicker.reset(); }
                document.getElementById('s-active').checked = true;
                document.getElementById('s-status').value = 'draft'; // new sections stage as draft
                label.textContent = 'New section';
                submitBtn.textContent = 'Create section';
                sync();
                new window.bootstrap.Offcanvas(document.getElementById('sectionOffcanvas')).show();
            });

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-edit-section');
                if (!btn) { return; }
                var d = JSON.parse(btn.getAttribute('data-section'));
                var id = btn.getAttribute('data-section-id');

                form.action = '/admin/merchandising/' + id;
                var existing = form.querySelector('[name="_method"]');
                if (!existing) { var m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'PUT'; form.prepend(m); }
                else { existing.value = 'PUT'; }

                typeEl.value = d.type || 'product_rail';
                sourceEl.value = d.source || '';
                document.getElementById('s-title').value = d.title || '';
                document.getElementById('s-subtitle').value = d.subtitle || '';
                document.getElementById('s-limit').value = d.item_limit || 8;
                document.getElementById('s-sort').value = d.sort_order || 0;
                document.getElementById('s-cta-label').value = d.cta_label || '';
                var s = d.settings || {};
                document.getElementById('s-eyebrow').value = s.eyebrow || '';
                document.getElementById('s-body').value = s.body || '';
                document.getElementById('s-image').value = s.image_url || '';
                document.getElementById('s-align').value = s.align || 'center';
                document.getElementById('s-theme').value = s.theme || 'default';
                document.getElementById('s-carousel').checked = !!parseInt(s.carousel || 0, 10);
                var lpEdit = form.querySelector('.gb-link-picker'); if (lpEdit && lpEdit._gbSet) { lpEdit._gbSet(d.cta_link || {}); }
                document.getElementById('s-starts').value = d.starts_at || '';
                document.getElementById('s-ends').value = d.ends_at || '';
                document.getElementById('s-active').checked = !!d.is_active;
                document.getElementById('s-status').value = d.status || 'published';

                sync();
                // Set the active ref value after sync() enabled the right control.
                if (d.type === 'banner_row') { if (window._gbBannerPicker) { window._gbBannerPicker.set(d.banners || []); } }
                else if (d.source === 'category') { document.getElementById('s-ref-category').value = d.source_ref || ''; }
                else if (d.source === 'brand') { document.getElementById('s-ref-brand').value = d.source_ref || ''; }
                else if (d.source === 'manual') { document.getElementById('s-ref-collection').value = d.source_ref || ''; }

                label.textContent = 'Edit section';
                submitBtn.textContent = 'Save changes';
                new window.bootstrap.Offcanvas(document.getElementById('sectionOffcanvas')).show();
            });

            sync();

            // --- Editorial image upload + live preview ---
            (function() {
                var urlInput = document.getElementById('s-image');
                var fileInput = document.getElementById('s-image-file');
                var clearBtn = document.getElementById('s-image-clear');
                var statusEl = document.getElementById('s-image-upload-status');
                var previewWrap = document.getElementById('s-image-preview-wrap');
                var previewImg = document.getElementById('s-image-preview');

                function updatePreview(url) {
                    if (url && url.length > 4) {
                        previewImg.src = url;
                        previewWrap.classList.remove('d-none');
                    } else {
                        previewWrap.classList.add('d-none');
                        previewImg.src = '';
                    }
                }

                if (urlInput) {
                    urlInput.addEventListener('input', function() { updatePreview(this.value.trim()); });
                }

                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        if (urlInput) { urlInput.value = ''; }
                        updatePreview('');
                    });
                }

                if (fileInput) {
                    fileInput.addEventListener('change', function() {
                        var file = this.files[0];
                        if (!file) { return; }
                        if (statusEl) { statusEl.textContent = 'Uploading…'; }

                        var fd = new FormData();
                        fd.append('image', file);
                        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                        fetch('{{ route('admin.merchandising.media.upload') }}', {
                            method: 'POST',
                            body: fd,
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.url) {
                                if (urlInput) { urlInput.value = data.url; }
                                updatePreview(data.url);
                                if (statusEl) { statusEl.textContent = 'Uploaded.'; }
                            } else {
                                if (statusEl) { statusEl.textContent = 'Upload failed.'; }
                            }
                        })
                        .catch(function() {
                            if (statusEl) { statusEl.textContent = 'Upload error.'; }
                        });

                        // Reset so the same file can be re-uploaded if needed
                        this.value = '';
                    });
                }

                // Populate preview when edit panel opens (d is the section data)
                // We hook into the edit button click to pre-fill the preview
                document.addEventListener('click', function(e) {
                    var btn = e.target.closest('.js-edit-section');
                    if (!btn) { return; }
                    setTimeout(function() {
                        var url = document.getElementById('s-image') ? document.getElementById('s-image').value : '';
                        updatePreview(url);
                    }, 50);
                });

                // Also populate on new section panel open (reset to empty)
                var newBtn = document.getElementById('btnNewSection');
                if (newBtn) {
                    newBtn.addEventListener('click', function() {
                        setTimeout(function() { updatePreview(''); }, 50);
                    });
                }
            })();
        })();
        </script>
    @endpush

    {{-- Quick-create a banner without leaving the page (MX4.4) --}}
    <div class="modal fade" id="bannerQuickModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mb-0">Create a banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Title</label><input class="form-control" id="qb-title" placeholder="e.g. Weekend Flash Sale"></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Placement</label>
                            <select class="form-select" id="qb-placement">
                                <option value="home_hero">Home hero</option>
                                <option value="home_strip">Home strip</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Colour</label>
                            <select class="form-select" id="qb-theme">
                                @foreach (array_keys(Banner::THEMES) as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <p class="fs-10 text-body-tertiary mt-2 mb-0">Creates a simple colour banner and adds it to this row. Upload artwork later on the Banners screen.</p>
                    <div class="text-danger fs-10 mt-1" id="qb-error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-phoenix-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="qb-save">Create &amp; add</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        (function () {
            // ── Banner-row picker: search, pick (ordered), remove, reorder, quick-create ──
            var searchInput = document.getElementById('s-banner-search');
            if (!searchInput) { return; }
            var results = document.getElementById('s-banner-results');
            var chips = document.getElementById('s-banner-chips');
            var emptyHint = document.getElementById('s-banner-empty');
            var SEARCH_URL = '{{ route('admin.banners.search') }}';
            var QUICK_URL = '{{ route('admin.banners.quick-store') }}';
            var CSRF = '{{ csrf_token() }}';

            function chosenIds() {
                return Array.prototype.slice.call(chips.querySelectorAll('[data-banner-id]'))
                    .map(function (c) { return parseInt(c.getAttribute('data-banner-id'), 10); });
            }
            function refreshEmpty() { emptyHint.style.display = chips.children.length ? 'none' : ''; }

            function swatch(b) {
                return b.thumb
                    ? '<span class="flex-shrink-0 rounded-1 border border-translucent" style="width:44px;height:30px;background:center/cover no-repeat url(' + b.thumb + ')"></span>'
                    : '<span class="flex-shrink-0 rounded-1" style="width:44px;height:30px;background:' + (b.gradient || '#3874ff') + '"></span>';
            }

            function addChip(b) {
                if (chosenIds().indexOf(b.id) !== -1) { return; } // no duplicates
                var chip = document.createElement('div');
                chip.className = 'gb-lever-item d-flex align-items-center gap-2 border border-translucent rounded-2 p-2';
                chip.setAttribute('data-banner-id', b.id);
                chip.innerHTML =
                    '<span class="gb-drag-handle text-body-tertiary" style="cursor:grab"><span class="fas fa-grip-vertical"></span></span>'
                    + swatch(b)
                    + '<span class="flex-grow-1 min-w-0 text-truncate fw-semibold fs-9">' + (b.title || 'Untitled') + '</span>'
                    + (b.live ? '' : '<span class="badge badge-phoenix badge-phoenix-secondary fs-10">Hidden</span>')
                    + '<input type="hidden" name="settings[banner_ids][]" value="' + b.id + '">'
                    + '<button type="button" class="btn btn-sm btn-phoenix-danger js-banner-remove" title="Remove"><span class="fas fa-xmark"></span></button>';
                chips.appendChild(chip);
                refreshEmpty();
            }

            function renderResults(list) {
                if (!list.length) { results.innerHTML = '<div class="px-3 py-2 text-body-tertiary fs-10">No banners found.</div>'; results.style.display = ''; return; }
                results.innerHTML = list.map(function (b) {
                    return '<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-2" data-add=\'' + JSON.stringify(b) + '\'>'
                        + swatch(b)
                        + '<span class="flex-grow-1 min-w-0 text-truncate fs-9">' + (b.title || 'Untitled') + '</span>'
                        + '<span class="badge badge-phoenix badge-phoenix-' + (b.live ? 'success' : 'secondary') + ' fs-10">' + (b.live ? 'Live' : 'Hidden') + '</span></button>';
                }).join('');
                results.style.display = '';
            }

            var t;
            searchInput.addEventListener('input', function () {
                clearTimeout(t);
                var q = searchInput.value.trim();
                t = setTimeout(function () {
                    fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.json(); })
                        .then(renderResults)
                        .catch(function () {});
                }, 220);
            });
            searchInput.addEventListener('focus', function () { if (searchInput.value.trim() === '') { searchInput.dispatchEvent(new Event('input')); } });

            results.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-add]');
                if (!btn) { return; }
                addChip(JSON.parse(btn.getAttribute('data-add')));
                results.style.display = 'none'; searchInput.value = ''; searchInput.focus();
            });

            chips.addEventListener('click', function (e) {
                var rm = e.target.closest('.js-banner-remove');
                if (rm) { rm.closest('[data-banner-id]').remove(); refreshEmpty(); }
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('#s-banner-search') && !e.target.closest('#s-banner-results')) { results.style.display = 'none'; }
            });

            if (window.Sortable) { window.Sortable.create(chips, { handle: '.gb-drag-handle', animation: 150 }); }

            // Reset + populate hooks used by the section form's new/edit handlers.
            window._gbBannerPicker = {
                reset: function () { chips.innerHTML = ''; results.style.display = 'none'; searchInput.value = ''; refreshEmpty(); },
                set: function (banners) { chips.innerHTML = ''; (banners || []).forEach(addChip); refreshEmpty(); },
            };

            // Quick-create modal.
            var qbSave = document.getElementById('qb-save');
            var qbError = document.getElementById('qb-error');
            document.getElementById('s-banner-new').addEventListener('click', function () {
                qbError.textContent = ''; document.getElementById('qb-title').value = '';
                new window.bootstrap.Modal(document.getElementById('bannerQuickModal')).show();
            });
            qbSave.addEventListener('click', function () {
                var title = document.getElementById('qb-title').value.trim();
                if (!title) { qbError.textContent = 'Give the banner a title.'; return; }
                qbSave.disabled = true;
                fetch(QUICK_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ title: title, placement: document.getElementById('qb-placement').value, theme: document.getElementById('qb-theme').value }),
                }).then(function (r) { return r.json(); }).then(function (b) {
                    addChip(b);
                    window.bootstrap.Modal.getInstance(document.getElementById('bannerQuickModal')).hide();
                }).catch(function () { qbError.textContent = 'Could not create the banner.'; })
                  .finally(function () { qbSave.disabled = false; });
            });
        })();
        </script>
    @endpush
@endsection
