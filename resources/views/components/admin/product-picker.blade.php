@props([
    'scope' => 'default',
    'onSelect' => null,
    'inStock' => false,
    'wholesale' => false,
    'placeholder' => 'Search product, SKU or brand…',
    'autofocus' => false,
])

{{--
    Premium searchable product selector (item #1 / #2).
    ---------------------------------------------------
    An accessible combobox over admin.products.search: search-as-you-type,
    full keyboard navigation (↑ ↓ Enter Esc), thumbnails, brand, category,
    stock, packaging and retail/wholesale prices. Resilient on poor networks —
    results are cached in memory, recent picks persist in localStorage and are
    offered when the field is empty or the network is down.

    Usage inside a Livewire component:
        <x-admin.product-picker scope="packaging" on-select="choose" :in-stock="false" />
    On selection it calls $wire.{onSelect}(variantId) and also dispatches a
    `product-picked` DOM event carrying the full row.
--}}
<div
    class="gb-pp"
    x-data="gbProductPicker({
        endpoint: '{{ route('admin.products.search') }}',
        scope: '{{ $scope }}',
        onSelect: @js($onSelect),
        inStock: {{ $inStock ? 'true' : 'false' }},
        showWholesale: {{ $wholesale ? 'true' : 'false' }},
    })"
    @keydown.escape.stop="close()"
    @click.outside="close()"
>
    <div class="gb-pp-field">
        <svg class="gb-pp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
            type="text"
            class="form-control gb-pp-input"
            role="combobox"
            aria-autocomplete="list"
            aria-controls="gb-pp-list-{{ $scope }}"
            :aria-expanded="open.toString()"
            :aria-activedescendant="active >= 0 ? 'gb-pp-opt-{{ $scope }}-' + active : null"
            autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
            placeholder="{{ $placeholder }}"
            @if ($autofocus) autofocus @endif
            x-model="query"
            x-ref="input"
            @input="onInput()"
            @focus="open = true"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="enter()"
            @keydown.home.prevent="active = 0; scrollActive()"
            @keydown.end.prevent="active = items.length - 1; scrollActive()"
        >
        <span class="gb-pp-spinner" x-show="loading" x-cloak aria-hidden="true"></span>
        <button type="button" class="gb-pp-clear" x-show="query.length" x-cloak @click="reset(); $refs.input.focus()"
                tabindex="-1" aria-label="Clear search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                 width="10" height="10" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <div class="gb-pp-panel" id="gb-pp-list-{{ $scope }}" role="listbox" x-ref="list"
         x-show="open && (items.length || isEmpty || loading)" x-cloak x-transition.opacity.duration.120ms>

        <div class="gb-pp-note" x-show="showRecents" x-cloak>Recent</div>
        <div class="gb-pp-note gb-pp-note--warn" x-show="offline" x-cloak>
            <span class="fas fa-wifi-slash me-1"></span>Offline — showing recent products
        </div>

        {{-- Skeleton rows while a fresh (uncached) query is in flight — never an empty panel. --}}
        <div x-show="loading && !items.length" x-cloak>
            <template x-for="n in 4" :key="n">
                <div class="gb-pp-opt gb-pp-opt--skel">
                    <span class="gb-skel gb-skel-avatar"></span>
                    <div class="gb-pp-body">
                        <span class="gb-skel gb-skel-line" style="width: 65%"></span>
                        <span class="gb-skel gb-skel-line gb-skel-line-sm" style="width: 40%; margin-top: .4rem"></span>
                    </div>
                    <span class="gb-skel gb-skel-line" style="width: 3rem"></span>
                </div>
            </template>
        </div>

        <template x-for="(item, idx) in items" :key="item.id">
            <div class="gb-pp-opt" role="option"
                 :id="'gb-pp-opt-{{ $scope }}-' + idx"
                 :data-idx="idx"
                 :class="{ 'is-active': active === idx }"
                 :aria-selected="(active === idx).toString()"
                 @mouseenter="active = idx"
                 @click="choose(item)">
                <img class="gb-pp-thumb" :src="item.thumb" alt="" width="40" height="40" loading="lazy"
                     x-on:error="$el.src='{{ asset('theme/img/placeholder.svg') }}'">
                <div class="gb-pp-body">
                    <div class="gb-pp-title">
                        <span x-text="item.name"></span>
                        <span class="gb-pp-variant" x-show="item.variant" x-text="item.variant"></span>
                    </div>
                    <div class="gb-pp-meta">
                        <span class="gb-pp-sku" x-text="item.sku"></span>
                        <span x-show="item.brand" x-text="'· ' + item.brand"></span>
                        <span x-show="item.category" x-text="'· ' + item.category"></span>
                        <span x-show="item.packaging > 0" class="gb-pp-pill" x-text="item.packaging + ' pack' + (item.packaging === 1 ? '' : 's')"></span>
                    </div>
                </div>
                <div class="gb-pp-right">
                    <div class="gb-pp-price">
                        <span x-text="item.retail"></span>
                        <span class="gb-pp-wholesale" x-show="showWholesale && item.wholesale" x-text="item.wholesale + ' whsl'"></span>
                    </div>
                    <span class="gb-pp-stock" :class="item.stock <= 0 ? 'is-out' : (item.low_stock ? 'is-low' : '')"
                          x-text="item.stock <= 0 ? 'Out' : item.stock + ' in stock'"></span>
                </div>
            </div>
        </template>

        <div class="gb-pp-empty" x-show="isEmpty" x-cloak>
            No products match “<span x-text="query"></span>”.
        </div>
    </div>
</div>

@once
    @push('styles')
        <link href="{{ asset('theme/css/skeleton.css') }}" rel="stylesheet">
        <link href="{{ asset('theme/css/product-picker.css') }}" rel="stylesheet">
    @endpush
    @push('scripts')
        <script src="{{ asset('theme/js/product-picker.js') }}" defer></script>
    @endpush
@endonce
