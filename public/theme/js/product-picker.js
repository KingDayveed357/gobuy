/* =============================================================================
   gobuy admin — Product Picker combobox
   -----------------------------------------------------------------------------
   A premium, accessible search-as-you-type selector for product variants.

   Resilience (poor-network first):
     • In-memory cache per query string — repeat searches are instant, no refetch.
     • In-flight requests are aborted when the query changes (no race / no stale UI).
     • Recent selections persist in localStorage and are shown when the field is
       empty, and are used as an offline fallback when a fetch fails.

   Registered as an Alpine component (Livewire 3 bundles Alpine), so it is used
   straight from Blade via  x-data="gbProductPicker({ ... })".
   ============================================================================= */
(function () {
    'use strict';

    function factory(config) {
        config = config || {};

        return {
            endpoint: config.endpoint,
            scope: config.scope || 'default',
            onSelect: config.onSelect || null,
            inStock: !!config.inStock,
            showWholesale: !!config.showWholesale,
            recentKey: 'gb_pp_recent_' + (config.scope || 'default'),

            query: '',
            open: false,
            loading: false,
            offline: false,
            active: -1,
            results: [],
            recents: [],

            // Non-reactive helpers (kept off the Alpine proxy).
            _cache: null,
            _controller: null,
            _timer: null,

            init: function () {
                this._cache = new Map();
                this.recents = this.loadRecents();
            },

            // ── Derived state ──────────────────────────────────────────────
            get items() {
                return this.query.trim().length < 2 ? this.recents : this.results;
            },
            get showRecents() {
                return this.query.trim().length < 2 && this.recents.length > 0;
            },
            get isEmpty() {
                return !this.loading && !this.offline &&
                    this.query.trim().length >= 2 && this.results.length === 0;
            },

            // ── Search ─────────────────────────────────────────────────────
            onInput: function () {
                this.open = true;
                this.active = -1;
                clearTimeout(this._timer);

                var q = this.query.trim();
                if (q.length < 2) {
                    this.results = [];
                    this.loading = false;
                    return;
                }
                if (this._cache.has(q)) {
                    this.results = this._cache.get(q);
                    this.loading = false;
                    return;
                }

                this.loading = true;
                var self = this;
                this._timer = setTimeout(function () { self.fetch(q); }, 220);
            },

            fetch: function (q) {
                var self = this;
                if (this._controller) { this._controller.abort(); }
                this._controller = new AbortController();

                var url = this.endpoint + '?q=' + encodeURIComponent(q) +
                    (this.inStock ? '&in_stock=1' : '');

                return fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: this._controller.signal,
                })
                    .then(function (res) {
                        if (!res.ok) { throw new Error('http ' + res.status); }
                        return res.json();
                    })
                    .then(function (json) {
                        self.offline = false;
                        self.results = (json && json.data) || [];
                        self._cache.set(q, self.results);
                        self.loading = false;
                    })
                    .catch(function (err) {
                        if (err && err.name === 'AbortError') { return; }
                        // Network trouble — degrade gracefully to a local recents filter.
                        self.offline = true;
                        var needle = q.toLowerCase();
                        self.results = self.recents.filter(function (r) {
                            return (r.name + ' ' + r.sku + ' ' + (r.brand || '')).toLowerCase().indexOf(needle) !== -1;
                        });
                        self.loading = false;
                    });
            },

            // ── Keyboard navigation ────────────────────────────────────────
            move: function (delta) {
                var n = this.items.length;
                if (!n) { return; }
                this.open = true;
                this.active = (this.active + delta + n) % n;
                this.scrollActive();
            },
            scrollActive: function () {
                var self = this;
                this.$nextTick(function () {
                    if (!self.$refs.list) { return; }
                    var el = self.$refs.list.querySelector('[data-idx="' + self.active + '"]');
                    if (el) { el.scrollIntoView({ block: 'nearest' }); }
                });
            },
            enter: function () {
                if (this.active >= 0 && this.items[this.active]) {
                    this.choose(this.items[this.active]);
                } else if (this.items.length === 1) {
                    this.choose(this.items[0]);
                }
            },

            // ── Selection ──────────────────────────────────────────────────
            choose: function (item) {
                if (!item) { return; }
                this.pushRecent(item);
                if (this.onSelect && this.$wire) {
                    this.$wire.call(this.onSelect, item.id);
                }
                this.$dispatch('product-picked', item);
                this.reset();
            },

            reset: function () {
                this.query = '';
                this.results = [];
                this.open = false;
                this.active = -1;
                this.loading = false;
            },
            close: function () {
                this.open = false;
                this.active = -1;
            },

            // ── Recents (localStorage) ─────────────────────────────────────
            loadRecents: function () {
                try {
                    var raw = localStorage.getItem(this.recentKey);
                    return raw ? JSON.parse(raw) : [];
                } catch (e) {
                    return [];
                }
            },
            pushRecent: function (item) {
                var list = this.loadRecents().filter(function (r) { return r.id !== item.id; });
                list.unshift(item);
                list = list.slice(0, 6);
                try { localStorage.setItem(this.recentKey, JSON.stringify(list)); } catch (e) { /* quota */ }
                this.recents = list;
            },
        };
    }

    if (window.Alpine) {
        window.Alpine.data('gbProductPicker', factory);
    } else {
        document.addEventListener('alpine:init', function () {
            window.Alpine.data('gbProductPicker', factory);
        });
    }
})();
