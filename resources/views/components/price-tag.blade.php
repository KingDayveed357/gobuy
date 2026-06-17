@php($currency = '₦')
<div {{ $attributes->merge(['class' => 'd-flex align-items-center flex-wrap mb-1']) }}>
    @if ($price->hasDiscount())
        <p class="me-2 text-body text-decoration-line-through mb-0">{{ $currency }}{{ number_format($price->retailPrice, 2) }}</p>
    @endif
    <h3 class="text-body-emphasis mb-0">{{ $currency }}{{ number_format($price->unitPrice, 2) }}</h3>
    @if ($price->hasDiscount())
        <span class="badge badge-phoenix badge-phoenix-success ms-2">Wholesale</span>
    @endif
</div>
