@php($currency = '₦')
<span {{ $attributes->merge(['class' => 'price-tag d-flex align-items-center flex-wrap']) }}>
    @if ($price->hasDiscount())
        <span class="me-2 text-body-tertiary text-decoration-line-through mb-0">{{ $currency }}{{ number_format($price->retailPrice, 2) }}</span>
    @endif
    <span class="h4 text-body-emphasis mb-0">{{ $currency }}{{ number_format($price->unitPrice, 2) }}</span>
    @if ($price->hasDiscount())
        <span class="badge badge-phoenix badge-phoenix-success ms-2">Wholesale</span>
    @endif
</span>
