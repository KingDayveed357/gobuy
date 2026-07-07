@props(['name' => 'link', 'value' => null, 'label' => 'Destination'])

@php
    $value = is_array($value) ? $value : [];
    $vType = $value['type'] ?? '';
    $vRef = $value['ref'] ?? '';
    $vLabel = $value['label'] ?? '';
    $isEntity = in_array($vType, ['product', 'category', 'brand'], true);
    $isFree = in_array($vType, ['search', 'url'], true);
@endphp

<div class="gb-link-picker position-relative" data-search-url="{{ route('admin.link-picker.search') }}">
    <label class="form-label">{{ $label }}</label>
    <select class="form-select form-select-sm gb-lp-type" name="{{ $name }}[type]">
        <option value="">— No link —</option>
        <option value="product" @selected($vType === 'product')>A product</option>
        <option value="category" @selected($vType === 'category')>A category</option>
        <option value="brand" @selected($vType === 'brand')>A brand</option>
        <option value="search" @selected($vType === 'search')>Search results</option>
        <option value="products" @selected($vType === 'products')>All products</option>
        <option value="home" @selected($vType === 'home')>Homepage</option>
        <option value="url" @selected($vType === 'url')>Custom URL</option>
    </select>

    {{-- Entity search (product / category / brand) --}}
    <div class="gb-lp-entity mt-2" style="{{ $isEntity ? '' : 'display:none;' }}">
        <input type="text" class="form-control form-control-sm gb-lp-search" placeholder="Type to search…" autocomplete="off">
        <div class="gb-lp-results list-group position-absolute w-100 shadow-sm" style="z-index:20; display:none; max-height:220px; overflow:auto;"></div>
        <div class="gb-lp-selected mt-1" style="{{ $isEntity && $vLabel ? '' : 'display:none;' }}">
            <span class="badge badge-phoenix badge-phoenix-info">
                <span class="gb-lp-selected-label">{{ $vLabel }}</span>
                <span class="fas fa-times gb-lp-clear ms-1" role="button" aria-label="Clear"></span>
            </span>
        </div>
    </div>

    {{-- Free text (search term / custom URL) --}}
    <input type="text" class="form-control form-control-sm mt-2 gb-lp-freetext"
           value="{{ $isFree ? $vRef : '' }}"
           placeholder="{{ $vType === 'url' ? 'https://…' : 'Search term' }}"
           style="{{ $isFree ? '' : 'display:none;' }}">

    <input type="hidden" class="gb-lp-ref" name="{{ $name }}[ref]" value="{{ $vRef }}">
    <input type="hidden" class="gb-lp-label" name="{{ $name }}[label]" value="{{ $vLabel }}">
</div>

@once
    @push('scripts')
        <script>
        (function () {
            function initPicker(root) {
                if (root._gbInit) { return; }
                root._gbInit = true;

                var typeSel = root.querySelector('.gb-lp-type');
                var entity = root.querySelector('.gb-lp-entity');
                var search = root.querySelector('.gb-lp-search');
                var results = root.querySelector('.gb-lp-results');
                var selected = root.querySelector('.gb-lp-selected');
                var selectedLabel = root.querySelector('.gb-lp-selected-label');
                var freetext = root.querySelector('.gb-lp-freetext');
                var refInput = root.querySelector('.gb-lp-ref');
                var labelInput = root.querySelector('.gb-lp-label');
                var url = root.getAttribute('data-search-url');
                var timer;

                function isEntity(t) { return t === 'product' || t === 'category' || t === 'brand'; }
                function isFree(t) { return t === 'search' || t === 'url'; }

                function sync() {
                    var t = typeSel.value;
                    entity.style.display = isEntity(t) ? '' : 'none';
                    freetext.style.display = isFree(t) ? '' : 'none';
                    if (isFree(t)) { freetext.placeholder = (t === 'url' ? 'https://…' : 'Search term'); }
                    selected.style.display = (isEntity(t) && labelInput.value) ? '' : 'none';
                }

                typeSel.addEventListener('change', function () {
                    refInput.value = ''; labelInput.value = ''; freetext.value = ''; if (search) { search.value = ''; }
                    results.style.display = 'none';
                    sync();
                });

                freetext.addEventListener('input', function () {
                    refInput.value = this.value; labelInput.value = this.value;
                });

                search.addEventListener('input', function () {
                    clearTimeout(timer);
                    var q = this.value.trim();
                    if (q.length < 2) { results.style.display = 'none'; return; }
                    timer = setTimeout(function () {
                        fetch(url + '?type=' + encodeURIComponent(typeSel.value) + '&q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                            .then(function (r) { return r.json(); })
                            .then(function (rows) {
                                results.innerHTML = (rows.length ? rows.map(function (row) {
                                    var sub = row.sublabel ? ' <span class="text-body-tertiary fs-10">' + row.sublabel + '</span>' : '';
                                    return '<button type="button" class="list-group-item list-group-item-action py-1 gb-lp-opt" data-value="' + row.value + '" data-label="' + String(row.label).replace(/"/g, '&quot;') + '">' + row.label + sub + '</button>';
                                }).join('') : '<div class="list-group-item py-1 text-body-tertiary fs-9">No matches</div>');
                                results.style.display = '';
                            });
                    }, 250);
                });

                results.addEventListener('click', function (e) {
                    var opt = e.target.closest('.gb-lp-opt'); if (!opt) { return; }
                    refInput.value = opt.getAttribute('data-value');
                    labelInput.value = opt.getAttribute('data-label');
                    selectedLabel.textContent = opt.getAttribute('data-label');
                    selected.style.display = ''; results.style.display = 'none'; search.value = '';
                });

                selected.addEventListener('click', function (e) {
                    if (e.target.closest('.gb-lp-clear')) { refInput.value = ''; labelInput.value = ''; selected.style.display = 'none'; }
                });

                document.addEventListener('click', function (e) { if (!root.contains(e.target)) { results.style.display = 'none'; } });

                // API used by the shared offcanvas forms (banner / section) on edit/new.
                root._gbSet = function (val) {
                    val = val || {};
                    typeSel.value = val.type || '';
                    refInput.value = val.ref || '';
                    labelInput.value = val.label || '';
                    freetext.value = isFree(val.type) ? (val.ref || '') : '';
                    if (selectedLabel) { selectedLabel.textContent = val.label || ''; }
                    sync();
                };
                root._gbReset = function () { root._gbSet({}); };

                sync();
            }

            window.GbLinkPicker = {
                initAll: function (scope) { (scope || document).querySelectorAll('.gb-link-picker').forEach(initPicker); },
            };
            document.addEventListener('DOMContentLoaded', function () { window.GbLinkPicker.initAll(); });
        })();
        </script>
    @endpush
@endonce
