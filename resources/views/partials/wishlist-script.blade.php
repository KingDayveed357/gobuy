@php($wids = auth('web')->check() ? auth('web')->user()->wishlistItems()->pluck('product_id')->values() : collect())

<script>
    window.GoBuyWishlist = {
        authenticated: {{ auth('web')->check() ? 'true' : 'false' }},
        ids: @json($wids),          // flat array of numeric product IDs — never changes shape
        csrf: '{{ csrf_token() }}',
        mergeUrl: '{{ route('wishlist.merge') }}',
        toggleBase: '{{ url('wishlist') }}',
    };

    (function () {
        var cfg = window.GoBuyWishlist;
        var LS_KEY = 'wishlist';
        var inFlight = {}; // de-dup: prevents concurrent toggles for the same product

        function lsGet() { try { return JSON.parse(localStorage.getItem(LS_KEY) || '[]'); } catch (e) { return []; } }
        function lsSet(arr) { localStorage.setItem(LS_KEY, JSON.stringify(arr)); }
        function toast(type, msg) { if (window.Toast && Toast[type]) { Toast[type](msg); } }

        // The navbar badge is a Livewire component (single source of truth) — we
        // push the authoritative count to it rather than touching the DOM, so no
        // surface can desync it.
        function pushCount(n) {
            if (window.Livewire && typeof Livewire.dispatch === 'function') {
                Livewire.dispatch('wishlist-updated', { count: n });
            }
        }

        function markHeart(id, wished) {
            document.querySelectorAll('[data-wishlist-toggle][data-product-id="' + id + '"]').forEach(function (btn) {
                btn.classList.toggle('is-wished', wished);
                btn.setAttribute('aria-pressed', wished ? 'true' : 'false');
                btn.title = wished ? 'Remove from wishlist' : 'Add to wishlist';
                var label = btn.querySelector('.wishlist-text');
                if (label) { label.textContent = wished ? 'Saved to wishlist' : 'Add to wishlist'; }
            });
        }

        function setButtonsBusy(id, busy) {
            document.querySelectorAll('[data-wishlist-toggle][data-product-id="' + id + '"]').forEach(function (btn) {
                btn.disabled = busy;
            });
        }

        function currentIds() { return cfg.authenticated ? cfg.ids.map(Number) : lsGet().map(Number); }

        // Expose a tiny API so the guest wishlist page reuses THIS controller
        // (one source of truth) instead of duplicating localStorage/badge logic.
        cfg.removeGuest = function (id) {
            var ids = lsGet().map(Number).filter(function (x) { return x !== Number(id); });
            lsSet(ids);
            markHeart(id, false);
            pushCount(ids.length);
            return ids.length;
        };
        cfg.guestIds = function () { return lsGet().map(Number); };

        function refresh() {
            currentIds().forEach(function (id) { markHeart(id, true); });
        }

        function init() {
            if (cfg.authenticated) {
                var guest = lsGet();
                if (guest.length) {
                    fetch(cfg.mergeUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ product_ids: guest }),
                    }).then(function (r) { return r.json(); }).then(function (d) {
                        cfg.ids = d.product_ids || [];
                        localStorage.removeItem(LS_KEY);
                        refresh();
                        pushCount(cfg.ids.length);
                        toast('success', 'Your saved items were added to your account.');
                    }).catch(refresh);
                    return;
                }
            }
            refresh();
            // Guests: push their localStorage count into the (server-mounted-at-0) badge.
            if (! cfg.authenticated) { pushCount(currentIds().length); }
        }

        function toggle(btn) {
            var id = parseInt(btn.getAttribute('data-product-id'), 10);
            if (! id || inFlight[id]) { return; } // ignore rapid repeat clicks
            var name = btn.getAttribute('data-product-name') || 'Item';

            if (cfg.authenticated) {
                // Read the slug directly from the button element — the route model
                // binding resolves {product} by slug, not numeric ID (getRouteKeyName
                // returns 'slug'), so we MUST use the slug in the URL or we get a 404.
                var slug = btn.getAttribute('data-product-slug') || id;
                var wasWished = btn.classList.contains('is-wished');
                inFlight[id] = true;
                setButtonsBusy(id, true);
                markHeart(id, ! wasWished); // optimistic

                fetch(cfg.toggleBase + '/' + slug + '/toggle', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(function (r) {
                        if (! r.ok) { throw new Error('HTTP ' + r.status); }
                        return r.json();
                    })
                    .then(function (d) {
                        markHeart(id, d.wished);
                        pushCount(d.count);
                        cfg.ids = d.wished ? cfg.ids.concat([id]) : cfg.ids.filter(function (x) { return Number(x) !== id; });
                        toast(d.wished ? 'success' : 'info', (d.wished ? 'Saved ' : 'Removed ') + name + (d.wished ? ' to wishlist' : ' from wishlist'));
                    })
                    .catch(function () {
                        markHeart(id, wasWished); // revert the optimistic update
                        toast('error', 'Could not update your wishlist — please try again.');
                    })
                    .finally(function () {
                        delete inFlight[id];
                        setButtonsBusy(id, false);
                    });
            } else {
                var ids = lsGet().map(Number);
                var i = ids.indexOf(id);
                var wished;
                if (i > -1) { ids.splice(i, 1); wished = false; } else { ids.push(id); wished = true; }
                lsSet(ids);
                markHeart(id, wished);
                pushCount(ids.length);
                toast(wished ? 'success' : 'info', (wished ? 'Saved ' : 'Removed ') + name + (wished ? ' to wishlist' : ' from wishlist'));
            }
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-wishlist-toggle]');
            if (! btn) { return; }
            e.preventDefault();
            e.stopPropagation();
            toggle(btn);
        });

        if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
    })();
</script>
