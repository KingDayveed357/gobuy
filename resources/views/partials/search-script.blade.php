<script>
    (function () {
        var SUGGEST_URL = '{{ route('search.suggestions') }}';
        var PRODUCTS_URL = '{{ route('products.index') }}';
        var RECENT_KEY = 'gb_recent_searches';

        function recentGet() { try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]'); } catch (e) { return []; } }
        function recentAdd(term) {
            term = (term || '').trim(); if (!term) { return; }
            var list = recentGet().filter(function (t) { return t.toLowerCase() !== term.toLowerCase(); });
            list.unshift(term);
            localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, 6)));
        }
        function esc(s) { return (s || '').replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
        function highlight(text, q) {
            if (!q) { return esc(text); }
            var i = text.toLowerCase().indexOf(q.toLowerCase());
            if (i < 0) { return esc(text); }
            return esc(text.slice(0, i)) + '<mark class="p-0 bg-warning-subtle">' + esc(text.slice(i, i + q.length)) + '</mark>' + esc(text.slice(i + q.length));
        }
        function debounce(fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); }; }

        function initBox(form) {
            var input = form.querySelector('[data-gb-search-input]');
            var panel = form.querySelector('[data-gb-search-panel]');
            var activeIndex = -1;

            function close() { panel.classList.remove('show'); input.setAttribute('aria-expanded', 'false'); activeIndex = -1; }
            function open() { panel.classList.add('show'); input.setAttribute('aria-expanded', 'true'); }
            function items() { return Array.prototype.slice.call(panel.querySelectorAll('[data-gb-item]')); }

            function setActive(idx) {
                var list = items();
                list.forEach(function (el) { el.classList.remove('active'); });
                activeIndex = idx;
                if (idx >= 0 && idx < list.length) { list[idx].classList.add('active'); list[idx].scrollIntoView({ block: 'nearest' }); }
            }

            function go(term) { recentAdd(term); window.location = PRODUCTS_URL + '?q=' + encodeURIComponent(term); }

            function renderIdle() {
                panel.classList.remove('gb-search-loading');
                var recent = recentGet();
                var html = '';
                if (recent.length) {
                    html += '<h6 class="dropdown-header d-flex justify-content-between">Recent <button type="button" class="btn btn-link p-0 fs-10" data-gb-clear-recent>Clear</button></h6>';
                    recent.forEach(function (t) { html += chip(t); });
                }
                html += '<h6 class="dropdown-header">' + (window.GoBuySearch && window.GoBuySearch.trending.length ? 'Trending' : '') + '</h6>';
                (window.GoBuySearch ? window.GoBuySearch.trending : []).forEach(function (t) { html += chip(t); });
                if (!html.trim()) { html = '<div class="px-3 py-3 text-body-tertiary fs-9">Start typing to search products…</div>'; }
                panel.innerHTML = html; open();
            }

            function chip(term) {
                return '<a href="#" class="dropdown-item d-flex align-items-center gap-2 fs-9" data-gb-item data-gb-term="' + esc(term) + '"><span class="fas fa-clock-rotate-left text-body-tertiary fs-10"></span>' + esc(term) + '</a>';
            }

            // MX3: loading feedback while the suggestion request is in flight.
            // Skeleton rows on a cold panel; a top progress shimmer when we
            // already have results to keep (stale-while-revalidate — no flicker).
            function beginSearch() {
                if (!panel.querySelector('[data-gb-item]')) {
                    var html = '<h6 class="dropdown-header">Products</h6>';
                    for (var i = 0; i < 3; i++) {
                        html += '<div class="dropdown-item d-flex align-items-center gap-2 py-2" aria-hidden="true">'
                            + '<span class="gb-skeleton flex-shrink-0" style="width:34px;height:34px;border-radius:.25rem"></span>'
                            + '<span class="flex-grow-1"><span class="gb-skeleton d-block mb-1" style="height:.7rem;width:70%"></span>'
                            + '<span class="gb-skeleton d-block" style="height:.6rem;width:40%"></span></span></div>';
                    }
                    panel.innerHTML = html;
                } else {
                    panel.classList.add('gb-search-loading');
                }
                open();
            }

            function renderResults(data) {
                var q = data.query;
                panel.classList.remove('gb-search-loading');
                var html = '';
                if (data.products.length) {
                    html += '<h6 class="dropdown-header">Products</h6>';
                    data.products.forEach(function (p) {
                        html += '<a href="' + p.url + '" class="dropdown-item d-flex align-items-center gap-2 py-2" data-gb-item>'
                            + '<img src="' + p.image + '" width="34" height="34" style="object-fit:contain" class="border border-translucent rounded-1" alt="">'
                            + '<span class="flex-grow-1 min-w-0"><span class="d-block text-truncate fs-9">' + highlight(p.name, q) + '</span>'
                            + '<span class="fs-10 text-body-tertiary">' + esc(p.category || '') + '</span></span>'
                            + '<span class="fs-9 fw-semibold text-nowrap">' + esc(p.price) + '</span></a>';
                    });
                }
                if (!html) { html = '<div class="px-3 py-3 text-body-tertiary fs-9">No matches for “' + esc(q) + '”. Press Enter to search anyway.</div>'; }
                else { html += '<a href="#" class="dropdown-item text-primary fw-semibold fs-9 border-top border-translucent" data-gb-item data-gb-term="' + esc(q) + '">See all results for “' + esc(q) + '”</a>'; }
                panel.innerHTML = html; open();
            }

            var fetchSuggest = debounce(function (q) {
                fetch(SUGGEST_URL + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { if (input.value.trim() === q) { renderResults(d); } })
                    .catch(function () { panel.classList.remove('gb-search-loading'); });
            }, 220);

            input.addEventListener('focus', function () { if (input.value.trim().length < 2) { renderIdle(); } });
            input.addEventListener('input', function () {
                var q = input.value.trim();
                if (q.length < 2) { renderIdle(); } else { beginSearch(); fetchSuggest(q); }
            });
            input.addEventListener('keydown', function (e) {
                var list = items();
                if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(activeIndex + 1, list.length - 1)); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(Math.max(activeIndex - 1, 0)); }
                else if (e.key === 'Enter') {
                    if (activeIndex >= 0 && list[activeIndex]) { e.preventDefault(); list[activeIndex].click(); }
                    else { recentAdd(input.value); /* let form submit */ }
                } else if (e.key === 'Escape') { close(); }
            });

            panel.addEventListener('click', function (e) {
                if (e.target.closest('[data-gb-clear-recent]')) { e.preventDefault(); localStorage.removeItem(RECENT_KEY); renderIdle(); return; }
                var item = e.target.closest('[data-gb-item]');
                if (item && item.dataset.gbTerm) { e.preventDefault(); go(item.dataset.gbTerm); }
            });

            document.addEventListener('click', function (e) { if (!form.contains(e.target)) { close(); } });
        }

        document.querySelectorAll('[data-gb-search]').forEach(initBox);
    })();
</script>
