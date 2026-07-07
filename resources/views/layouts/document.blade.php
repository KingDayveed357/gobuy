<!DOCTYPE html>
<html
    lang="en"
    dir="ltr"
    data-page-size="{{ $document->getPageSize() }}"
    data-orientation="{{ $document->getOrientation() }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Prevent search engines from indexing print documents --}}
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $document->getTitle() }}</title>

    {{-- Preconnect to Google Fonts for fast load --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,300;0,6..12,400;0,6..12,600;0,6..12,700;0,6..12,800;0,6..12,900;1,6..12,400&display=swap"
        rel="stylesheet"
    >

    {{-- Centralized document stylesheet — the only CSS this layout needs --}}
    <link rel="stylesheet" href="{{ asset('css/document.css') }}?v={{ filemtime(public_path('css/document.css')) }}">

    {{-- FontAwesome for icons in the action bar --}}
    <link
        rel="stylesheet"
        href="{{ asset('theme/vendors/fontawesome/fontawesome.min.css') }}"
        media="screen"
    >

    @stack('document-styles')
</head>
<body class="doc-body {{ $document->getOrientation() === 'landscape' ? 'doc-body--landscape' : '' }}">

    {{-- ── Screen-only action bar ─────────────────────────────────────────────── --}}
    {{-- Hidden on print via CSS: .doc-action-bar { display: none } in @media print --}}
    <div class="doc-action-bar no-print" role="toolbar" aria-label="Document actions">
        <div class="doc-action-bar__left">
            @if ($document->getBackUrl())
                <a href="{{ $document->getBackUrl() }}" class="doc-btn doc-btn-back">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M19 12H5M12 5l-7 7 7 7"/>
                    </svg>
                    Back
                </a>
            @endif
            <div>
                <div class="doc-action-bar__title">{{ $document->getDocumentType() }}</div>
                <div class="doc-action-bar__subtitle">{{ $document->getReference() }}</div>
            </div>
        </div>
        <div class="doc-action-bar__right">
            <button
                id="doc-print-btn"
                class="doc-btn doc-btn-primary"
                onclick="window.print()"
                type="button"
            >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Print / Save as PDF
            </button>
        </div>
    </div>

    {{-- ── Document page ────────────────────────────────────────────────────────── --}}
    <main class="doc-page" role="main">

        {{-- Optional watermark (DRAFT, COPY, VOID, etc.) --}}
        @if ($document->showWatermark() && $document->getWatermarkText())
            <div class="doc-watermark" aria-hidden="true">{{ $document->getWatermarkText() }}</div>
        @endif

        {{-- Document content is provided by the concrete document template --}}
        @yield('document')

    </main>

    {{-- Auto-trigger print dialog on page load.
         The brief 400ms delay gives the browser time to fully render
         fonts, images, and tables before the dialog opens — eliminating
         the blank-page/missing-content problem common in rushed implementations. --}}
    <script>
        (function () {
            var ready = document.readyState;
            if (ready === 'complete') {
                setTimeout(function () { window.print(); }, 400);
            } else {
                window.addEventListener('load', function () {
                    setTimeout(function () { window.print(); }, 400);
                });
            }
        }());
    </script>

    @stack('document-scripts')
</body>
</html>
