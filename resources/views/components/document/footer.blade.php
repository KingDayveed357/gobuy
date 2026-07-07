@props([
    'branding',  // array from DocumentBrandingService
    'document',  // DocumentInterface
])

{{--
    Document Footer Component
    ─────────────────────────
    Renders the bottom section of every document:
    left  → disclaimer + legal notice
    right → store website + print timestamp

    The @page @bottom-right rule in document.css handles page numbers
    in Chrome/Edge PDF mode. This footer only appears once, on the last
    page (or on single-page documents). For page numbers on every printed
    page, the CSS Paged Media counter is the correct mechanism.
--}}
<footer class="doc-footer">
    <div class="doc-footer__left">
        @if ($branding['disclaimer'])
            <p class="doc-footer__disclaimer">{{ $branding['disclaimer'] }}</p>
        @endif
        @if ($branding['legal_notice'])
            <p class="doc-footer__legal">{{ $branding['legal_notice'] }}</p>
        @endif
        {{ $slot }}
    </div>

    <div class="doc-footer__right">
        @if ($branding['website'])
            <div>{{ parse_url($branding['website'], PHP_URL_HOST) ?? $branding['website'] }}</div>
        @endif
        <div>Printed {{ now()->format('M j, Y g:i A') }}</div>
        <div class="doc-footer__page print-only">Page <span class="page-num"></span></div>
    </div>
</footer>
