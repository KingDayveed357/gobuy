{{--
    Global Admin Command Palette
    ============================
    Triggered by Ctrl+K, Cmd+K, or the search button in the top navbar.
    The search index (window.__gbPalette) is permission-filtered by PHP and
    embedded inline — no API calls needed for search.

    The JS (command-palette.js) is lazy-loaded on first trigger to minimise
    initial page weight. Once loaded it stays cached for the session.
--}}
@php
    $searchIndex = app(\App\Admin\Support\AdminSearchIndex::class)->resolve(auth('admin')->user());
@endphp

{{-- Inline search index: permission-filtered, URL-resolved --}}
<script>
window.__gbPalette = @json($searchIndex, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
</script>

{{-- ARIA live region for screen readers --}}
<div id="gcp-live" role="status" aria-live="polite" aria-atomic="true" class="visually-hidden"></div>

{{-- Backdrop + dialog shell --}}
<div
    id="gcp-backdrop"
    class="gcp-backdrop"
    role="dialog"
    aria-modal="true"
    aria-label="Command palette — search admin pages and actions"
    aria-hidden="true"
>
    <div id="gcp-dialog" class="gcp-dialog">

        {{-- Search input row --}}
        <div class="gcp-search-wrap">
            <svg class="gcp-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>

            <input
                id="gcp-input"
                class="gcp-input"
                type="text"
                role="combobox"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                placeholder="Search pages, actions, settings…"
                aria-label="Search admin"
                aria-expanded="true"
                aria-controls="gcp-results"
                aria-autocomplete="list"
                aria-haspopup="listbox"
                maxlength="100"
            >

            {{-- Clear button --}}
            <button
                id="gcp-clear"
                class="gcp-clear-btn"
                type="button"
                aria-label="Clear search"
                tabindex="-1"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                     width="10" height="10" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>

            {{-- Cancel button for mobile/touch screens --}}
            <button
                id="gcp-cancel"
                class="gcp-cancel-btn"
                type="button"
                aria-label="Cancel search"
            >
                Cancel
            </button>
        </div>

        {{-- Results scroll area --}}
        <div
            id="gcp-results"
            class="gcp-results"
            role="listbox"
            aria-label="Search results"
        >
            {{-- Populated dynamically by command-palette.js --}}
        </div>

        {{-- Footer keyboard hints --}}
        <div class="gcp-footer" aria-hidden="true">
            <span class="gcp-footer-hint">
                <kbd>↑</kbd><kbd>↓</kbd> Navigate
            </span>
            <span class="gcp-footer-hint">
                <kbd>↵</kbd> Go
            </span>
            <span class="gcp-footer-hint">
                <kbd>Esc</kbd> Close
            </span>
            <span class="gcp-footer-spacer"></span>
            <span class="gcp-footer-hint" id="gcp-result-count"></span>
        </div>
    </div>
</div>

{{--
    Lazy-load strategy:
    The heavy JS is deferred until the user first presses Ctrl+K or clicks the trigger.
    A tiny inline bootstrap handles the keyboard shortcut immediately and loads the
    full script on demand. This keeps initial page weight minimal.
--}}
<script>
(function () {
    'use strict';

    var loaded   = false;
    var CSS_HREF = '{{ asset('theme/css/command-palette.css') }}';
    var JS_SRC   = '{{ asset('theme/js/command-palette.js') }}';

    // Preload CSS immediately (tiny, no render block) so it is cached when needed
    var link = document.createElement('link');
    link.rel  = 'preload';
    link.as   = 'style';
    link.href = CSS_HREF;
    document.head.appendChild(link);

    function loadAndToggle() {
        if (loaded) {
            if (window.GobuyPalette) { window.GobuyPalette.toggle(); }
            return;
        }

        // Inject CSS
        var styleLink = document.createElement('link');
        styleLink.rel  = 'stylesheet';
        styleLink.href = CSS_HREF;
        document.head.appendChild(styleLink);

        // Inject JS, then open
        var script   = document.createElement('script');
        script.src   = JS_SRC;
        script.defer = false;
        script.onload = function () {
            loaded = true;
            if (window.GobuyPalette) { window.GobuyPalette.open(); }
        };
        document.body.appendChild(script);
    }

    // ── Ctrl+K / Cmd+K bootstrap (fires before full JS is loaded) ──────────
    document.addEventListener('keydown', function (e) {
        var isMac  = /Mac|iPad|iPhone|iPod/.test(navigator.userAgent || navigator.platform);
        var combo  = isMac ? (e.metaKey && !e.ctrlKey) : e.ctrlKey;
        if (combo && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            loadAndToggle();
        }
    }, { capture: true });

    // ── Trigger button click ────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.gcp-trigger');
        if (btn) { e.preventDefault(); loadAndToggle(); }
    });

    // ── Idle preload — after page is fully settled, preload the script ──────
    function idlePreload() {
        if (loaded) { return; }
        var s  = document.createElement('script');
        s.src  = JS_SRC;
        s.defer = true;
        s.onload = function () { loaded = true; };
        document.body.appendChild(s);

        var sl = document.createElement('link');
        sl.rel  = 'stylesheet';
        sl.href = CSS_HREF;
        document.head.appendChild(sl);
    }

    if ('requestIdleCallback' in window) {
        requestIdleCallback(idlePreload, { timeout: 3000 });
    } else {
        setTimeout(idlePreload, 2500);
    }
})();
</script>
