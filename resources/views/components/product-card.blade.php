@props(['product'])

<div {{ $attributes->merge(['class' => 'product-card-container h-100']) }}>
    <div class="position-relative text-decoration-none product-card h-100">
        <div class="d-flex flex-column justify-content-between h-100">
            <div>
                <div class="border border-1 border-translucent rounded-3 position-relative mb-3 gb-template-product-media">
                    <button class="btn btn-wish btn-wish-primary z-2 d-toggle-container" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist">
                        <span class="fas fa-heart d-block-hover" data-fa-transform="down-1"></span>
                        <span class="far fa-heart d-none-hover" data-fa-transform="down-1"></span>
                    </button>
                    <img class="img-fluid" src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">
                    @if ($product->is_featured)
                        <span class="badge text-bg-success fs-10 product-verified-badge">Featured<span class="fas fa-check ms-1"></span></span>
                    @endif
                </div>
                <a class="stretched-link" href="{{ route('products.show', $product) }}">
                    <h6 class="mb-2 lh-sm line-clamp-3 product-name">{{ $product->name }}</h6>
                </a>
                <p class="fs-9">
                    @for ($i = 0; $i < 5; $i++)
                        <span class="fa fa-star text-warning"></span>
                    @endfor
                    <span class="text-body-quaternary fw-semibold ms-1">({{ max(1, $product->id % 97) }} people rated)</span>
                </p>
            </div>
            <div>
                <p class="fs-9 text-body-tertiary mb-2">{{ $product->category->name }}</p>
                <div class="mb-1">
                    <x-price-tag :product="$product" />
                </div>
                @if ($product->isInStock())
                    <p class="text-success fw-bold fs-9 lh-1 mb-0">In stock</p>
                @else
                    <p class="text-body-tertiary fw-semibold fs-9 lh-1 mb-0">Sold out</p>
                @endif
                <form action="{{ route('cart.store') }}" method="POST" class="position-relative z-2 mt-3">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit" class="btn btn-sm btn-phoenix-primary w-100 gb-template-cart-button" @disabled(! $product->isInStock())>
                        <span class="fas fa-shopping-cart me-2"></span>Add to cart
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
