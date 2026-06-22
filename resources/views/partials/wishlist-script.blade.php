@php($wids = auth('web')->check() ? auth('web')->user()->wishlistItems()->pluck('product_id')->values() : collect())

<script>
    window.GoBuyWishlist = {
        authenticated: {{ auth('web')->check() ? 'true' : 'false' }},
        ids: @json($wids),
        csrf: '{{ csrf_token() }}',
        mergeUrl: '{{ route('wishlist.merge') }}',
        toggleBase: '{{ url('wishlist') }}',
    };

    (function () {
        var cfg = window.GoBuyWishlist;
        var LS_KEY = 'wishlist';

        function lsGet() { try { return JSON.parse(localStorage.getItem(LS_KEY) || '[]'); } catch (e) { return []; } }
        function lsSet(arr) { localStorage.setItem(LS_KEY, JSON.stringify(arr)); }
        function toast(type, msg) { if (window.Toast && Toast[type]) { Toast[type](msg); } }

        function setBadge(n) {
            document.querySelectorAll('[data-wishlist-count]').forEach(function (el) {
                el.textContent = n;
                el.classList.toggle('d-none', n < 1);
            });
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

        function currentIds() { return cfg.authenticated ? cfg.ids.map(Number) : lsGet().map(Number); }

        function refresh() {
            var ids = currentIds();
            setBadge(ids.length);
            ids.forEach(function (id) { markHeart(id, true); });
        }

        // Merge a guest's localStorage wishlist into the account after sign-in.
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
                        toast('success', 'Your saved items were added to your account.');
                    }).catch(refresh);
                    return;
                }
            }
            refresh();
        }

        function toggle(btn) {
            var id = parseInt(btn.getAttribute('data-product-id'), 10);
            if (!id) { return; }
            var name = btn.getAttribute('data-product-name') || 'Item';

            if (cfg.authenticated) {
                var wasWished = btn.classList.contains('is-wished');
                markHeart(id, !wasWished); // optimistic
                fetch(cfg.toggleBase + '/' + id + '/toggle', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json' },
                }).then(function (r) { return r.json(); }).then(function (d) {
                    markHeart(id, d.wished);
                    setBadge(d.count);
                    cfg.ids = d.wished ? cfg.ids.concat([id]) : cfg.ids.filter(function (x) { return Number(x) !== id; });
                    toast(d.wished ? 'success' : 'info', (d.wished ? 'Saved ' : 'Removed ') + name + (d.wished ? ' to wishlist' : ' from wishlist'));
                }).catch(function () { markHeart(id, wasWished); });
            } else {
                var ids = lsGet().map(Number);
                var i = ids.indexOf(id);
                var wished;
                if (i > -1) { ids.splice(i, 1); wished = false; } else { ids.push(id); wished = true; }
                lsSet(ids);
                markHeart(id, wished);
                setBadge(ids.length);
                toast(wished ? 'success' : 'info', (wished ? 'Saved ' : 'Removed ') + name + (wished ? ' to wishlist' : ' from wishlist'));
            }
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-wishlist-toggle]');
            if (!btn) { return; }
            e.preventDefault();
            e.stopPropagation();
            toggle(btn);
        });

        if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
    })();
</script>
