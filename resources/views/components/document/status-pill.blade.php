@props([
    'tone' => 'neutral', // 'success' | 'danger' | 'warning' | 'info' | 'neutral'
])

{{--
    Status Pill Component
    ─────────────────────
    Print-safe status badge using border + background (no box-shadow,
    no pseudo-elements, no gradients) so it renders correctly in both
    browser print and PDF export.

    Usage:
        <x-document.status-pill tone="success">Paid</x-document.status-pill>
        <x-document.status-pill tone="danger">Failed</x-document.status-pill>
        <x-document.status-pill tone="warning">Pending</x-document.status-pill>
--}}
<span class="doc-status doc-status--{{ $tone }}">{{ $slot }}</span>
