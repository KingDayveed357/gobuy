@foreach($products as $product)
    @php
        $rPrice = app(\App\Modules\Pricing\Services\PricingEngine::class)->priceForProduct($product, auth('web')->user(), 1);
        $primaryVariant = $product->primaryVariant();
    @endphp
    <tr class="hover-actions-trigger btn-reveal-trigger position-static">
        <td class="align-middle white-space-nowrap ps-0 py-0">
            <a class="border border-translucent rounded-2 d-inline-block" href="{{ route('products.show', $product) }}">
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="53" class="img-fluid rounded" style="object-fit: contain; max-height: 53px;">
            </a>
        </td>
        <td class="products align-middle pe-11">
            <a class="fw-semibold mb-0 line-clamp-2" href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
        </td>
        <td class="color align-middle white-space-nowrap fs-9 text-body">
            {{ $primaryVariant?->label() }}
        </td>
        <td class="size align-middle white-space-nowrap text-body-tertiary fs-9 fw-semibold">
            {{ $primaryVariant?->sku }}
        </td>
        <td class="price align-middle text-body fs-9 fw-semibold text-end">
            {{ money($rPrice->unitPrice) }}
        </td>
        <td class="total align-middle fw-bold text-body-highlight text-end text-nowrap pe-0">
            <button class="btn btn-sm text-body-quaternary text-body-tertiary-hover me-2 remove-wishlist-btn" data-id="{{ $product->id }}">
                <span class="fas fa-trash"></span>
            </button>
            <form action="{{ route('cart.set-quantity') }}" method="POST" class="d-inline-block">
                @csrf
                <input type="hidden" name="product_variant_id" value="{{ $primaryVariant?->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn btn-primary fs-10" @disabled(!$primaryVariant || $primaryVariant->stock < 1)>
                    <span class="fas fa-shopping-cart me-1 fs-10"></span>Add to cart
                </button>
            </form>
        </td>
    </tr>
@endforeach
