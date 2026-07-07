@props([
    'items', // array<string, string|null> — associative ['Label' => 'Value']
    'cols' => 3, // int — number of grid columns (2 or 3)
])

{{--
    Metadata Grid Component
    ───────────────────────
    Renders a responsive grid of key/value pairs — used for order metadata
    (date, payment method, status, delivery method) at the top of documents.

    Usage:
        <x-document.metadata-grid :items="[
            'Order Date'   => $order->placed_at->format('M j, Y'),
            'Order Status' => $order->status->label(),
            'Payment'      => $order->payment_method->label(),
        ]" />
--}}
<div class="doc-meta-grid" style="{{ $cols == 2 ? 'grid-template-columns: repeat(2, 1fr)' : '' }}">
    @foreach ($items as $label => $value)
        @if ($value !== null && $value !== '')
            <div class="doc-meta-item">
                <div class="doc-meta-item__label">{{ $label }}</div>
                <div class="doc-meta-item__value">{{ $value }}</div>
            </div>
        @endif
    @endforeach
</div>
