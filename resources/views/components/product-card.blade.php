@props(['product', 'urgency' => false])

@php($cardVariant = $product->primaryVariant())
@php($cardStock = $cardVariant?->stock ?? 0)
@php($cardPrice = app(\App\Modules\Pricing\Services\PricingEngine::class)->priceForProduct($product, auth('web')->user(), 1))

<div {{ $attributes->merge(['class' => 'product-card-container h-100']) }}>
    <div class="position-relative text-decoration-none product-card h-100">
        <div class="d-flex flex-column justify-content-between h-100">
            <div>
                <div class="border border-1 border-translucent rounded-3 position-relative mb-3 gb-template-product-media">
                    <button class="btn btn-wish btn-wish-primary z-2 d-toggle-container" style="z-index:5;" type="button" data-wishlist-toggle data-product-id="{{ $product->id }}" data-product-slug="{{ $product->slug }}" data-product-variant-id="{{ $cardVariant?->id }}" data-product-name="{{ $product->name }}" data-product-url="{{ route('products.show', $product) }}" data-product-image="{{ $product->imageUrl() }}" data-product-price="{{ money($cardPrice->unitPrice) }}" data-product-category="{{ $product->category?->name ?? 'Product' }}" data-product-variant="{{ $cardVariant?->label() ?? 'Default' }}" data-product-stock="{{ $cardVariant?->stock ?? 0 }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Add to wishlist">
                        <span class="fas fa-heart d-block-hover" data-fa-transform="down-1"></span>
                        <span class="far fa-heart d-none-hover" data-fa-transform="down-1"></span>
                    </button>
                    <img class="img-fluid gb-lazy-img" src="{{ $product->thumbUrl() }}" alt="{{ $product->name }}" width="300" height="300" loading="lazy" decoding="async">
                    @if ($product->is_featured)
                        <span class="badge text-bg-success fs-10 product-verified-badge">Featured<span class="fas fa-check ms-1"></span></span>
                    @endif
                    @if ($urgency)
                        <span class="gb-sale-badge">Sale</span>
                    @endif
                </div>
                <a class="stretched-link" href="{{ route('products.show', $product) }}">
                    <h6 class="mb-2 lh-sm line-clamp-2 product-name">{{ $product->name }}</h6>
                </a>
                {{-- Real verified-purchase rating; hidden entirely until the product has reviews. --}}
                @if ($product->rating_count > 0)
                    @php($cardRating = (int) round($product->rating_avg))
                    <p class="fs-9 mb-2" aria-label="Rated {{ number_format($product->rating_avg, 1) }} out of 5 by {{ $product->rating_count }} {{ \Illuminate\Support\Str::plural('customer', $product->rating_count) }}">
                        @for ($i = 1; $i <= 5; $i++)
                            <span class="fa{{ $i <= $cardRating ? 's' : 'r' }} fa-star text-warning" aria-hidden="true"></span>
                        @endfor
                        <span class="text-body-tertiary ms-1">({{ $product->rating_count }})</span>
                    </p>
                @endif
            </div>
            <div>
                <p class="fs-9 text-body-tertiary mb-2">{{ $product->category->name }}</p>
                <div class="mb-1">
                    <x-price-tag :product="$product" />
                </div>
                @if ($urgency && $cardStock > 0 && $cardStock <= 15)
                    {{-- Flash-sale urgency: a low-stock progress bar drives conversion. --}}
                    <div class="gb-urgency">
                        <div class="progress" style="height:5px;">
                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ max(12, (int) round($cardStock / 15 * 100)) }}%"></div>
                        </div>
                        <p class="fs-10 text-danger fw-bold lh-1 mb-0 mt-1">Only {{ $cardStock }} left</p>
                    </div>
                @elseif ($product->isInStock())
                    <p class="text-success fw-bold fs-9 lh-1 mb-0">In stock</p>
                @else
                    <p class="text-body-tertiary fw-semibold fs-9 lh-1 mb-0">Sold out</p>
                @endif
                @if ($product->hasVariants())
                    <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-phoenix-primary w-100 mt-3 position-relative z-2">
                        <span class="fas fa-sliders me-2"></span>Choose options
                    </a>
                @else
                    {{-- One delegated handler (cart-script) enhances ALL these forms with a
                         single fetch → Toast + Livewire 'cart-updated' — no per-card component
                         (keeps a 12-product listing at zero extra hydrations on the 1-core VPS).
                         With JS off it posts to cart.store and redirects. --}}
                    <form action="{{ route('cart.store') }}" method="POST" data-add-to-cart class="position-relative z-2 mt-3">
                        @csrf
                        <input type="hidden" name="product_variant_id" value="{{ $cardVariant?->id }}">
                        <button type="submit" class="btn btn-sm btn-phoenix-primary w-100 gb-template-cart-button" @disabled(! $product->isInStock())>
                            <span class="fas fa-shopping-cart me-2"></span>Add to cart
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
