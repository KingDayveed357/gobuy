@props([
    'title', // string — the section label
])

{{--
    Section Title Component
    ───────────────────────
    A small uppercase all-caps label with a bottom border rule used to
    visually group content blocks on a document. Consistent across all
    document types — update here to change every instance.

    Usage:
        <x-document.section-title title="Line Items" />
        <x-document.section-title title="Delivery Address" />
--}}
<h2 class="doc-section-title">{{ $title }}</h2>
