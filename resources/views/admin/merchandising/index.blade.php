@extends('admin.layouts.app')

@use('App\Modules\Marketing\Enums\SectionType')

@section('title', 'Merchandising — gobuy admin')
@section('page-title', 'Merchandising')

@section('content')
    <x-admin.page-header :title="$page->isHome() ? 'Homepage merchandising' : $page->title.' — sections'" :subtitle="$page->isHome() ? 'Compose, order and schedule the storefront homepage — no developer required.' : 'Building the /p/'.$page->slug.' landing page.'">
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
                        </div>

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

                {{-- Banner placement ref (banner row) --}}
                <div class="mb-3 js-ref-placement" style="display:none;">
                    <label class="form-label">Banner placement</label>
                    <select class="form-select" name="source_ref" id="s-ref-placement" disabled>
                        <option value="home_hero">Home hero</option>
                        <option value="home_strip">Home strip</option>
                    </select>
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
            var refPlacement = document.querySelector('.js-ref-placement');
            var refCollection = document.querySelector('.js-ref-collection');
            var whenProduct = document.querySelectorAll('.js-when-product');
            var whenEditorial = document.querySelector('.js-when-editorial');
            var mediaOnly = document.querySelector('.js-media-only');
            var richtextOnly = document.querySelector('.js-richtext-only');

            // Only one source_ref control is active at a time so a single value posts.
            function setRef(which) {
                var map = { category: refCategory, brand: refBrand, placement: refPlacement, collection: refCollection };
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

                if (type === 'banner_row') { setRef('placement'); }
                else if (isProduct && sourceEl.value === 'category') { setRef('category'); }
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
                var lpEdit = form.querySelector('.gb-link-picker'); if (lpEdit && lpEdit._gbSet) { lpEdit._gbSet(d.cta_link || {}); }
                document.getElementById('s-starts').value = d.starts_at || '';
                document.getElementById('s-ends').value = d.ends_at || '';
                document.getElementById('s-active').checked = !!d.is_active;
                document.getElementById('s-status').value = d.status || 'published';

                sync();
                // Set the active ref value after sync() enabled the right control.
                if (d.type === 'banner_row') { document.getElementById('s-ref-placement').value = d.source_ref || 'home_hero'; }
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
@endsection
