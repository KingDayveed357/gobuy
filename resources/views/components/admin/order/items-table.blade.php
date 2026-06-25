@props(['order'])

<div class="card mb-4">
    <div class="card-body p-0">
        <div class="table-responsive scrollbar">
            <table class="table fs-9 mb-0 border-top border-translucent">
                <thead>
                    <tr>
                        <th class="border-top border-translucent ps-3">Item</th>
                        <th class="border-top border-translucent text-center">Qty</th>
                        <th class="border-top border-translucent text-end">Unit Price</th>
                        <th class="border-top border-translucent text-end pe-3">Total</th>
                        <th class="border-top border-translucent pe-3 text-end" style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        @php
                            $product = $item->variant?->product;
                            $imageUrl = $product?->imageUrl() ?? asset('theme/img/placeholder.svg');
                            $variantLabel = $item->variant?->label();
                            $isDefaultVariant = $item->variant?->is_default ?? true;
                        @endphp
                        <tr class="hover-actions-trigger btn-reveal-trigger">
                            <td class="align-middle ps-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar avatar-xl">
                                        <img src="{{ $imageUrl }}" class="rounded border border-translucent bg-body-tertiary object-fit-cover" alt="{{ $item->name }}" />
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-body-emphasis mb-1">{{ $item->name }}</div>
                                        <div class="d-flex align-items-center gap-2">
                                            @if(!$isDefaultVariant && $variantLabel)
                                                <span class="badge badge-phoenix badge-phoenix-primary fs-10">{{ $variantLabel }}</span>
                                            @endif
                                            <span class="fs-10 text-body-tertiary fw-normal">SKU: {{ $item->sku }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle text-center fw-semibold text-body-highlight">{{ $item->quantity }}</td>
                            <td class="align-middle text-end text-body-highlight">{{ money($item->unit_price) }}</td>
                            <td class="align-middle text-end fw-semibold text-body-emphasis pe-3">{{ money($item->line_total) }}</td>
                            <td class="align-middle text-end pe-3">
                                @if($product)
                                    <a href="{{ route('admin.products.edit', $product->slug) }}" class="btn btn-sm btn-phoenix-secondary btn-reveal">
                                        <span class="fas fa-eye"></span>
                                    </a>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-secondary">Deleted</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
