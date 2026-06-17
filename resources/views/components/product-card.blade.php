@props(['product'])

<div {{ $attributes->merge(['class' => 'product-card-container h-100']) }}>
    <div class="position-relative product-card h-100">
        <div class="border border-translucent rounded-3 position-relative mb-3 gb-media">
            @if ($product->is_featured)
                <span class="badge text-bg-success fs-10 product-verified-badge">Featured<span class="fas fa-check ms-1"></span></span>
            @endif
            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">
        </div>

        <a class="stretched-link text-decoration-none" href="{{ route('products.show', $product) }}">
            <h6 class="mb-2 lh-sm line-clamp-2 text-body-emphasis gb-name">{{ $product->name }}</h6>
        </a>
        <p class="fs-9 text-body-tertiary mb-1">{{ $product->category->name }}</p>
        <p class="fs-9 mb-2">
            @for ($i = 0; $i < 5; $i++)
                <span class="fa fa-star text-warning"></span>
            @endfor
        </p>

        <div class="mt-auto">
            <div class="mb-2">
                <x-price-tag :product="$product" />
            </div>
            @if ($product->isInStock())
                <p class="text-success fw-semibold fs-9 lh-1 mb-2">In stock</p>
            @else
                <p class="text-body-tertiary fw-semibold fs-9 lh-1 mb-2">Sold out</p>
            @endif
            <form action="{{ route('cart.store') }}" method="POST" class="position-relative z-2">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="btn btn-sm btn-phoenix-primary w-100" @disabled(! $product->isInStock())>
                    <span class="fas fa-shopping-cart me-1"></span>Add to cart
                </button>
            </form>
        </div>
    </div>
</div>
