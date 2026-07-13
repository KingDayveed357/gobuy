@extends('layouts.storefront')

@section('title', 'Wishlist — Quintessential Mart')

@section('content')
<section class="pt-5 pb-9">
    <div class="container-small cart">
        <nav class="mb-3" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Wishlist</li>
            </ol>
        </nav>

        <div class="d-flex align-items-center justify-content-between mb-5">
            <h2 class="mb-0">
                Wishlist
                <span class="text-body-tertiary fw-normal ms-2 fs-5" id="wishlist-count-label">(0)</span>
            </h2>
        </div>

        {{-- Empty State --}}
        <div class="d-none text-center py-9 border-y border-translucent" id="empty-wishlist-msg">
            <div class="mb-3"><span class="far fa-heart fs-3 text-body-tertiary"></span></div>
            <h5 class="mb-1">Your wishlist is empty</h5>
            <p class="text-body-tertiary mb-4">Tap the heart on any product to save it here.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary">Browse products</a>
        </div>

        {{-- Products Table --}}
        <div id="wishlist-table-wrapper" class="d-none">
            <div class="border-y border-translucent">
                <div class="table-responsive scrollbar">
                    <table class="table fs-9 mb-0">
                        <thead>
                            <tr>
                                <th class="align-middle" scope="col" style="width:7%;"></th>
                                <th class="align-middle" scope="col" style="width:30%; min-width:250px;">PRODUCTS</th>
                                <th class="align-middle" scope="col" style="width:16%;"></th>
                                <th class="align-middle" scope="col" style="width:10%;"></th>
                                <th class="align-middle text-end" scope="col" style="width:10%;">PRICE</th>
                                <th class="align-middle text-end pe-0" scope="col" style="width:27%;"> </th>
                            </tr>
                        </thead>
                        <tbody id="profile-wishlist-table-body">
                            {{-- Skeleton rows shown while loading --}}
                            @for ($s = 0; $s < 3; $s++)
                                <tr class="wishlist-skeleton">
                                    <td class="py-3"><div class="placeholder-glow"><span class="placeholder rounded-2" style="width:53px;height:53px;display:inline-block;"></span></div></td>
                                    <td class="py-3"><div class="placeholder-glow"><span class="placeholder col-10 mb-1 d-block"></span><span class="placeholder col-6"></span></div></td>
                                    <td class="py-3"><div class="placeholder-glow"><span class="placeholder col-8"></span></div></td>
                                    <td class="py-3"><div class="placeholder-glow"><span class="placeholder col-8"></span></div></td>
                                    <td class="py-3 text-end"><div class="placeholder-glow"><span class="placeholder col-10"></span></div></td>
                                    <td class="py-3 text-end"><div class="placeholder-glow"><span class="placeholder col-8"></span></div></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination Controls --}}
            <div id="wishlist-pagination" class="d-none mt-4">
                <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
                    {{-- Showing X–Y of Z --}}
                    <p class="text-body-tertiary fs-9 mb-0" id="wishlist-showing"></p>

                    {{-- Page buttons --}}
                    <nav aria-label="Wishlist pages">
                        <ul class="pagination pagination-sm mb-0" id="wishlist-page-list"></ul>
                    </nav>
                </div>
            </div>
        </div>

        {{-- Error state --}}
        <div class="d-none text-center py-6 border-y border-translucent" id="wishlist-error-msg">
            <p class="text-danger mb-3"><span class="fas fa-exclamation-circle me-2"></span>Could not load your wishlist.</p>
            <button class="btn btn-sm btn-outline-primary" id="wishlist-retry-btn">Try again</button>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var PER_PAGE    = 9;
    var currentPage = 1;
    var totalPages  = 1;
    var totalItems  = 0;

    var tbody      = document.getElementById('profile-wishlist-table-body');
    var wrapper    = document.getElementById('wishlist-table-wrapper');
    var emptyMsg   = document.getElementById('empty-wishlist-msg');
    var errorMsg   = document.getElementById('wishlist-error-msg');
    var countLabel = document.getElementById('wishlist-count-label');
    var pagination = document.getElementById('wishlist-pagination');
    var showingEl  = document.getElementById('wishlist-showing');
    var pageList   = document.getElementById('wishlist-page-list');
    var retryBtn   = document.getElementById('wishlist-retry-btn');

    // ── Helpers ────────────────────────────────────────────────────────────
    function getIds() {
        // Reuse the GoBuyWishlist global if available (single source of truth),
        // otherwise fall back to reading localStorage directly.
        if (window.GoBuyWishlist && typeof window.GoBuyWishlist.guestIds === 'function') {
            return window.GoBuyWishlist.guestIds();
        }
        try { return JSON.parse(localStorage.getItem('wishlist') || '[]').map(Number); } catch (e) { return []; }
    }

    function showState(state) {
        // state: 'loading' | 'empty' | 'table' | 'error'
        wrapper.classList.toggle('d-none', state !== 'table' && state !== 'loading');
        emptyMsg.classList.toggle('d-none', state !== 'empty');
        errorMsg.classList.toggle('d-none', state !== 'error');
    }

    function setSkeletons(visible) {
        document.querySelectorAll('.wishlist-skeleton').forEach(function (el) {
            el.style.display = visible ? '' : 'none';
        });
    }

    // ── Pagination UI ──────────────────────────────────────────────────────
    function renderPagination(current, last, from, to, total) {
        // Showing label
        showingEl.textContent = total > 0
            ? 'Showing ' + from + '–' + to + ' of ' + total + ' items'
            : '';

        pagination.classList.toggle('d-none', last <= 1);
        if (last <= 1) return;

        pageList.innerHTML = '';

        // Previous
        var prevLi = document.createElement('li');
        prevLi.className = 'page-item' + (current <= 1 ? ' disabled' : '');
        prevLi.innerHTML = '<button class="page-link" aria-label="Previous" ' + (current <= 1 ? 'disabled' : '') + '>'
            + '<span aria-hidden="true">&laquo;</span></button>';
        if (current > 1) {
            prevLi.querySelector('button').addEventListener('click', function () { loadPage(current - 1); });
        }
        pageList.appendChild(prevLi);

        // Page numbers — show at most 5 around current page
        var start = Math.max(1, current - 2);
        var end   = Math.min(last, current + 2);

        if (start > 1) {
            pageList.appendChild(makePageItem(1, current));
            if (start > 2) { pageList.appendChild(makeEllipsis()); }
        }
        for (var p = start; p <= end; p++) {
            pageList.appendChild(makePageItem(p, current));
        }
        if (end < last) {
            if (end < last - 1) { pageList.appendChild(makeEllipsis()); }
            pageList.appendChild(makePageItem(last, current));
        }

        // Next
        var nextLi = document.createElement('li');
        nextLi.className = 'page-item' + (current >= last ? ' disabled' : '');
        nextLi.innerHTML = '<button class="page-link" aria-label="Next" ' + (current >= last ? 'disabled' : '') + '>'
            + '<span aria-hidden="true">&raquo;</span></button>';
        if (current < last) {
            nextLi.querySelector('button').addEventListener('click', function () { loadPage(current + 1); });
        }
        pageList.appendChild(nextLi);
    }

    function makePageItem(num, current) {
        var li = document.createElement('li');
        li.className = 'page-item' + (num === current ? ' active' : '');
        var btn = document.createElement('button');
        btn.className = 'page-link';
        btn.textContent = num;
        btn.setAttribute('aria-label', 'Page ' + num);
        if (num === current) { btn.setAttribute('aria-current', 'page'); }
        btn.addEventListener('click', function () { if (num !== current) { loadPage(num); } });
        li.appendChild(btn);
        return li;
    }

    function makeEllipsis() {
        var li = document.createElement('li');
        li.className = 'page-item disabled';
        li.innerHTML = '<span class="page-link">…</span>';
        return li;
    }

    // ── Core loader ────────────────────────────────────────────────────────
    function loadPage(page) {
        var ids = getIds();
        currentPage = page;

        if (ids.length === 0) {
            countLabel.textContent = '(0)';
            showState('empty');
            return;
        }

        // Show table shell with skeletons while fetching
        showState('loading');
        setSkeletons(true);
        wrapper.classList.remove('d-none');

        fetch('{{ route('wishlist.items') }}?page=' + page, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ ids: ids, page: page, per_page: PER_PAGE }),
        })
        .then(function (res) {
            if (! res.ok) { throw new Error('HTTP ' + res.status); }
            return res.json();
        })
        .then(function (data) {
            totalItems = data.total || 0;
            totalPages = data.last_page || 1;

            // Update count label
            countLabel.textContent = '(' + totalItems + ')';

            setSkeletons(false);

            if (totalItems === 0 || ! data.html) {
                showState('empty');
                return;
            }

            // Inject rows
            document.querySelectorAll('#profile-wishlist-table-body tr:not(.wishlist-skeleton)').forEach(function (tr) { tr.remove(); });
            var tmp = document.createElement('tbody');
            tmp.innerHTML = data.html;
            while (tmp.firstChild) { tbody.appendChild(tmp.firstChild); }

            showState('table');
            renderPagination(data.current_page, data.last_page, data.from, data.to, data.total);

            // Wire up remove buttons injected by the server-rendered fragment.
            wireRemoveButtons();
        })
        .catch(function (err) {
            console.error('Wishlist load error:', err);
            setSkeletons(false);
            showState('error');
        });
    }

    // ── Remove buttons (delegated) ─────────────────────────────────────────
    function wireRemoveButtons() {
        tbody.querySelectorAll('.remove-wishlist-btn').forEach(function (btn) {
            // Guard against double-binding on re-renders
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var id = parseInt(btn.getAttribute('data-id'), 10);

                // Delegate removal to the global wishlist controller (single source
                // of truth); it updates localStorage, marks hearts, and pushes the
                // badge count via Livewire.
                var remaining;
                if (window.GoBuyWishlist && typeof window.GoBuyWishlist.removeGuest === 'function') {
                    remaining = window.GoBuyWishlist.removeGuest(id);
                } else {
                    var list = getIds().filter(function (x) { return x !== id; });
                    localStorage.setItem('wishlist', JSON.stringify(list));
                    remaining = list.length;
                }

                // Optimistic row removal — reload current (or previous) page if it empties
                var visibleRows = tbody.querySelectorAll('tr:not(.wishlist-skeleton)').length;
                if (visibleRows <= 1 && currentPage > 1) {
                    loadPage(currentPage - 1);
                } else {
                    loadPage(currentPage);
                }
            });
        });
    }

    // Retry button
    retryBtn.addEventListener('click', function () { loadPage(currentPage); });

    // ── Boot ───────────────────────────────────────────────────────────────
    // The GoBuyWishlist script initialises before this block, so guestIds() is
    // already populated from localStorage by the time we call loadPage.
    function boot() { loadPage(1); }

    if (document.readyState !== 'loading') { boot(); }
    else { document.addEventListener('DOMContentLoaded', boot); }
})();
</script>
@endpush
