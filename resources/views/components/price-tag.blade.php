<div {{ $attributes->merge(['class' => 'd-flex align-items-center flex-wrap mb-1']) }}>
    @if ($price->hasDiscount())
        <p class="me-2 text-body-tertiary text-decoration-line-through mb-0">{{ money($price->retailPrice) }}</p>
    @endif
    <h3 class="text-body-emphasis mb-0 {{ $price->hasDiscount() ? 'text-danger' : '' }}">{{ money($price->unitPrice) }}</h3>
    @if ($price->isWholesale)
        <span class="badge badge-phoenix badge-phoenix-primary ms-2">Wholesale</span>
    @elseif ($price->isOnSale)
        <span class="badge badge-phoenix badge-phoenix-danger ms-2">-{{ $price->discountPercent() }}%</span>
    @endif
</div>
