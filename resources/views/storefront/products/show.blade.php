@extends('layouts.storefront')

@section('title', $product->name.' — gobuy')

@php
    $selected = $product->primaryVariant();
    $gallery = $product->getMedia(\App\Modules\Catalog\Models\Product::MEDIA_COLLECTION);
    $relatedSwiper = '{"slidesPerView":1.18,"spaceBetween":10,"watchOverflow":true,"breakpoints":{"576":{"slidesPerView":2.1,"spaceBetween":10},"768":{"slidesPerView":3.1,"spaceBetween":12},"992":{"slidesPerView":4.1,"spaceBetween":12},"1200":{"slidesPerView":5.1,"spaceBetween":12},"1540":{"slidesPerView":6.1,"spaceBetween":12}}}';

    $sel = $selected ? $variantData[$selected->id] : ['unit' => 0, 'retail' => 0, 'stock' => 0, 'sku' => '', 'hasDiscount' => false, 'cartQty' => 0];

    $ogImage = \Illuminate\Support\Str::startsWith($product->imageUrl(), 'http') ? $product->imageUrl() : url($product->imageUrl());
    $productJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'image' => [$ogImage],
        'description' => \Illuminate\Support\Str::limit(strip_tags($product->description), 300),
        'sku' => $sel['sku'] ?: $selected?->sku,
        'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
        'offers' => [
            '@type' => 'Offer',
            'url' => route('products.show', $product),
            'priceCurrency' => 'NGN',
            'price' => $selected ? number_format($sel['unit']->toNaira(), 2, '.', '') : '0.00',
            'availability' => $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ],
    ];
@endphp

@push('meta')
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 160) }}">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 160) }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ route('products.show', $product) }}">
    <meta property="product:price:amount" content="{{ number_format($sel['unit']->toNaira(), 2, '.', '') }}">
    <meta property="product:price:currency" content="NGN">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $product->name }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <script type="application/ld+json">{!! json_encode($productJsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/photoswipe@5.4.3/dist/photoswipe.css">
    <style>
        .pswp__bg {
            background: rgba(10, 10, 10, 0.95) !important;
            backdrop-filter: blur(8px);
        }
        .pswp-info-panel {
            position: fixed; /* Changed from absolute to fixed to prevent scrolling issues */
            bottom: 0;
            left: 0;
            width: 100%;
            max-height: 60vh;
            background: rgba(255, 255, 255, 0.98);
            color: #000;
            padding: 1.5rem;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            z-index: 1000000;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
        }
        
        /* Dark mode overrides */
        html[data-bs-theme="dark"] .pswp-info-panel {
            background: rgba(20, 24, 36, 0.98) !important;
            color: #fff !important;
        }
        @media (prefers-color-scheme: dark) {
            html:not([data-bs-theme="light"]) .pswp-info-panel {
                background: rgba(20, 24, 36, 0.98) !important;
                color: #fff !important;
            }
        }
        
        .pswp-info-panel.open {
            transform: translateY(0);
        }
        .pswp-info-panel-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }
        .pswp-info-close {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--phoenix-body-bg, #fff);
            border: 1px solid var(--phoenix-border-color, #e3e6ed);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
            transition: all 0.2s;
        }
        html[data-bs-theme="dark"] .pswp-info-close {
            background: var(--phoenix-body-bg, #141824);
            border-color: var(--phoenix-border-color, #31374a);
        }
        .pswp-info-close:hover {
            background: var(--phoenix-tertiary-bg);
        }
        #pd-main-image {
            cursor: zoom-in;
            transition: transform 0.2s;
        }
        #pd-main-image:hover {
            transform: scale(1.02);
        }
        /* Custom UI Info button in toolbar */
        .pswp__button--info-btn {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endpush

@section('content')
    <section class="py-0 pt-5">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                    @if ($product->category)
                        <li class="breadcrumb-item"><a href="{{ route('products.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a></li>
                    @endif
                    <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
                </ol>
            </nav>

            <div class="row g-5 mb-5 mb-lg-8" data-product-details="data-product-details">
                <div class="col-12 col-lg-6">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-2 col-lg-12 col-xl-2">
                            @if ($gallery->count() > 1)
                                <div class="d-flex d-md-flex flex-row flex-md-column flex-lg-row flex-xl-column gap-2 overflow-auto scrollbar">
                                    @foreach ($gallery as $media)
                                        <button type="button" class="pd-thumb border rounded-2 p-1 bg-body {{ $loop->first ? 'border-primary' : 'border-translucent' }} flex-shrink-0" style="width: 64px; height: 64px;" data-full="{{ $media->getUrl() }}">
                                            <img class="img-fluid w-100 h-100 object-fit-contain" src="{{ $media->getUrl() }}" alt="{{ $product->name }}">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="col-12 col-md-10 col-lg-12 col-xl-10">
                            <div class="d-flex align-items-center border border-translucent rounded-3 text-center p-5 h-100" style="min-height: 360px;">
                                <img id="pd-main-image" class="img-fluid" style="max-height: 360px; object-fit: contain; width: 100%;" src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex">
                        <button type="button" class="btn btn-lg btn-outline-warning rounded-pill w-100 me-3 px-2 px-sm-4 fs-9 fs-sm-8 gb-wish" data-wishlist-toggle data-product-id="{{ $product->id }}" data-product-slug="{{ $product->slug }}" data-product-name="{{ $product->name }}">
                            <span class="far fa-heart me-2"></span><span class="fas fa-heart me-2 d-none"></span><span class="wishlist-text">Add to wishlist</span>
                        </button>
                        {{-- Livewire add-to-cart (no reload). Variant/qty UI stays client-side
                             (zero network); this fires one server action via CartService. --}}
                        <livewire:product.product-purchase
                            :product="$product"
                            :selected-id="$selected?->id"
                            :cart-qty="$sel['cartQty']"
                            :stock="$sel['stock']" />
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="d-flex flex-column justify-content-between h-100">
                        <div>
                            <div class="d-flex flex-wrap align-items-center">
                                @if ($product->rating_count > 0)
                                    @php($rounded = (int) round($product->rating_avg))
                                    <div class="me-2">
                                        @for ($i = 1; $i <= 5; $i++)<span class="fa{{ $i <= $rounded ? 's' : 'r' }} fa-star text-warning"></span>@endfor
                                    </div>
                                    <a href="#tab-reviews" class="text-primary fw-semibold mb-2 text-decoration-none" onclick="document.getElementById('reviews-tab')?.click();">{{ number_format($product->rating_avg, 1) }} ({{ $product->rating_count }} {{ \Illuminate\Support\Str::plural('review', $product->rating_count) }})</a>
                                @else
                                    <p class="text-body-tertiary fw-semibold mb-2 fs-9">No reviews yet — be the first.</p>
                                @endif
                            </div>
                            <h3 class="mb-3 lh-sm">{{ $product->name }}</h3>
                            <div class="d-flex flex-wrap align-items-start mb-3">
                                <span class="badge text-bg-success fs-9 rounded-pill me-2 fw-semibold">SKU: <span id="pd-sku">{{ $sel['sku'] }}</span></span>
                            </div>
                            
                            <div class="d-flex flex-wrap align-items-center mb-2">
                                <h1 id="pd-price" class="me-3 mb-0 {{ $sel['hasDiscount'] ? 'text-danger' : 'text-body-emphasis' }}">{{ money($sel['unit']) }}</h1>
                                <p id="pd-old" class="text-body-quaternary text-decoration-line-through fs-6 mb-0 me-3 {{ $sel['hasDiscount'] ? '' : 'd-none' }}">{{ money($sel['retail']) }}</p>
                                @if ($sel['hasDiscount'])
                                    @php($discountPercent = $sel['retail']->kobo > 0 ? (int) round((($sel['retail']->kobo - $sel['unit']->kobo) / $sel['retail']->kobo) * 100) : 0)
                                    <p id="pd-discount-badge" class="text-warning fw-bolder fs-6 mb-0">{{ $discountPercent }}% off</p>
                                @endif
                            </div>

                            @auth
                                @if (auth()->user()->isWholesale())
                                    <div class="alert alert-subtle-info fs-9 py-2 mb-2">Wholesale pricing is applied to your account.</div>
                                @endif
                            @endauth

                            <p id="pd-stock" class="fw-semibold fs-7 mb-2 {{ $sel['stock'] > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $sel['stock'] > 0 ? 'In stock ('.$sel['stock'].' available)' : 'Out of stock' }}
                            </p>

                            {{-- Back-in-stock capture — shown only while the selected variant is sold out. --}}
                            <div id="pd-back-in-stock" class="mb-3 {{ $sel['stock'] > 0 ? 'd-none' : '' }}">
                                <form action="{{ route('back-in-stock.store') }}" method="POST" class="d-flex flex-wrap gap-2 align-items-center">
                                    @csrf
                                    <input type="hidden" name="product_variant_id" id="pd-bis-variant" value="{{ $selected?->id }}">
                                    <input type="email" name="email" class="form-control form-control-sm" style="max-width:15rem;" placeholder="you@email.com" value="{{ auth('web')->user()?->email }}" required>
                                    <button type="submit" class="btn btn-sm btn-phoenix-warning"><span class="fas fa-bell me-1"></span>Notify me when available</button>
                                </form>
                                <p class="fs-9 text-body-tertiary mt-1 mb-0">We’ll email you the moment it’s restocked — no spam.</p>
                            </div>

                            <p class="mb-2 text-body-secondary">{{ \Illuminate\Support\Str::limit($product->description, 280) }}</p>

                            <x-social-share class="mb-2" :url="route('products.show', $product)" :title="$product->name" :price="money($sel['unit'])" />
                        </div>

                        <div>
                            @if ($product->variants->count() > 1)
                                <div class="mb-3">
                                    <p class="fw-semibold mb-2 text-body">{{ $product->options->first()?->name ?? 'Option' }}</p>
                                    <div class="d-flex align-items-center">
                                        <select class="form-select w-auto" id="pd-variant-select">
                                            @foreach ($product->variants as $v)
                                                <option value="{{ $v->id }}" @selected($selected && $v->id === $selected->id) @disabled($v->stock < 1)>
                                                    {{ $v->label() }}{{ $v->stock < 1 ? ' — sold out' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="row g-3 g-sm-5 align-items-end">
                                <div class="col-12 col-sm">
                                    <p class="fw-semibold mb-2 text-body">Quantity : </p>
                                    <div class="d-flex justify-content-between align-items-end">
                                        <div class="d-flex flex-between-center" data-quantity="data-quantity">
                                            <button class="btn btn-phoenix-primary px-3" data-type="minus" id="pd-qty-minus"><span class="fas fa-minus"></span></button>
                                            <input class="form-control text-center input-spin-none bg-transparent border-0 outline-none" style="width:50px;" type="number" id="pd-qty" value="{{ max(1, $sel['cartQty']) }}" min="1" max="{{ max(1, $sel['stock']) }}">
                                            <button class="btn btn-phoenix-primary px-3" data-type="plus" id="pd-qty-plus"><span class="fas fa-plus"></span></button>
                                        </div>
                                    </div>
                                    {{-- Subtle max-quantity hint — shown when the shopper reaches the available stock. --}}
                                    <p id="pd-qty-hint" class="fs-9 text-body-tertiary mt-2 mb-0 d-none">
                                        <span class="fas fa-circle-info me-1"></span><span id="pd-qty-hint-text"></span>
                                    </p>

                                    {{-- Bulk / wholesale demand capture — a lead, not a backorder. --}}
                                    <div class="mt-2">
                                        <a class="fs-9 text-primary text-decoration-none" data-bs-toggle="collapse" href="#pd-bulk-form" role="button" aria-expanded="false">
                                            <span class="fas fa-boxes-stacked me-1"></span>Need more than <span id="pd-bulk-max">{{ max(1, $sel['stock']) }}</span>? Request a bulk quantity
                                        </a>
                                        <div class="collapse mt-2" id="pd-bulk-form">
                                            <form action="{{ route('bulk-requests.store') }}" method="POST" class="row g-2" style="max-width:32rem;">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <input type="hidden" name="product_variant_id" id="pd-bulk-variant" value="{{ $selected?->id }}">
                                                <div class="col-6"><input name="name" class="form-control form-control-sm" placeholder="Name" value="{{ auth('web')->user()?->name }}" required></div>
                                                <div class="col-6"><input type="email" name="email" class="form-control form-control-sm" placeholder="Email" value="{{ auth('web')->user()?->email }}" required></div>
                                                <div class="col-6"><input name="phone" class="form-control form-control-sm" placeholder="Phone (optional)" value="{{ auth('web')->user()?->phone }}"></div>
                                                <div class="col-6"><input type="number" name="quantity" min="1" class="form-control form-control-sm" placeholder="Quantity needed" required></div>
                                                <div class="col-12"><textarea name="note" rows="2" class="form-control form-control-sm" placeholder="Anything else? (optional)"></textarea></div>
                                                <div class="col-12"><button type="submit" class="btn btn-sm btn-phoenix-primary">Submit request</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-0">
        <div class="container-small">
            <ul class="nav nav-underline fs-9 mb-4" id="productTab" role="tablist">
                <li class="nav-item"><a class="nav-link active" id="description-tab" data-bs-toggle="tab" href="#tab-description" role="tab" aria-controls="tab-description" aria-selected="true">Description</a></li>
                @if ($product->specifications->isNotEmpty())
                    <li class="nav-item"><a class="nav-link" id="specification-tab" data-bs-toggle="tab" href="#tab-specification" role="tab" aria-controls="tab-specification" aria-selected="false">Specifications</a></li>
                @endif
                <li class="nav-item"><a class="nav-link" id="reviews-tab" data-bs-toggle="tab" href="#tab-reviews" role="tab" aria-controls="tab-reviews" aria-selected="false">Ratings &amp; reviews</a></li>
            </ul>
            <div class="row gx-3 gy-7">
                <div class="col-12 col-lg-7 col-xl-8">
                    <div class="tab-content" id="productTabContent">
                        <div class="tab-pane pe-lg-6 pe-xl-12 fade show active text-body-emphasis" id="tab-description" role="tabpanel" aria-labelledby="description-tab">
                            <p class="mb-0">{{ $product->description }}</p>
                            @if ($product->brand)
                                <p class="fs-9 text-body-tertiary mt-3 mb-0">Brand: <span class="fw-semibold text-body">{{ $product->brand->name }}</span></p>
                            @endif
                        </div>
                        
                        @if ($product->specifications->isNotEmpty())
                            <div class="tab-pane pe-lg-6 pe-xl-12 fade" id="tab-specification" role="tabpanel" aria-labelledby="specification-tab">
                                <h3 class="mb-0 ms-4 fw-bold">Specifications</h3>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40%"> </th>
                                            <th style="width: 60%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($product->specifications as $spec)
                                            <tr>
                                                <td class="bg-body-highlight align-middle">
                                                    <h6 class="mb-0 text-body text-uppercase fw-bolder px-4 fs-9 lh-sm">{{ $spec->label }}</h6>
                                                </td>
                                                <td class="px-5 mb-0">{{ $spec->value }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="tab-pane fade" id="tab-reviews" role="tabpanel" aria-labelledby="reviews-tab">
                            <div class="bg-body-emphasis rounded-3 p-4 border border-translucent">
                                <div class="d-flex align-items-center flex-wrap mb-4">
                                    <h2 class="fw-bolder me-3 mb-0">{{ $product->rating_count > 0 ? number_format($product->rating_avg, 1) : '—' }}<span class="fs-8 text-body-quaternary fw-bold">/5</span></h2>
                                    @php($rounded = (int) round($product->rating_avg))
                                    <div class="me-3">
                                        @for ($i = 1; $i <= 5; $i++)<span class="fa{{ $i <= $rounded ? 's' : 'r' }} fa-star text-warning fs-6"></span>@endfor
                                    </div>
                                    <p class="text-body mb-0 fw-semibold fs-8">{{ $product->rating_count }} verified {{ \Illuminate\Support\Str::plural('review', $product->rating_count) }}</p>
                                </div>

                                {{-- Write a review --}}
                                @auth
                                    @if ($canReview)
                                        <form action="{{ route('reviews.store', $product) }}" method="POST" class="border border-translucent rounded-3 p-3 mb-4">
                                            @csrf
                                            <h5 class="mb-3">Write a review</h5>
                                            <div class="mb-3" style="max-width: 220px;">
                                                <label class="form-label fs-9">Rating</label>
                                                <select class="form-select" name="rating" required>
                                                    @for ($r = 5; $r >= 1; $r--)<option value="{{ $r }}">{{ $r }} star{{ $r > 1 ? 's' : '' }}</option>@endfor
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fs-9">Title <span class="text-body-tertiary">(optional)</span></label>
                                                <input class="form-control" type="text" name="title" maxlength="120" placeholder="Sum it up">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fs-9">Your review</label>
                                                <textarea class="form-control" name="body" rows="4" placeholder="What did you like or dislike?"></textarea>
                                            </div>
                                            <button class="btn btn-primary" type="submit">Submit review</button>
                                        </form>
                                    @else
                                        <div class="alert alert-subtle-info fs-9">Only customers with a delivered order for this product can review it (one review each).</div>
                                    @endif
                                @else
                                    <div class="alert alert-subtle-info fs-9 mb-4"><a href="{{ route('login') }}">Sign in</a> to review products you've purchased.</div>
                                @endauth

                                {{-- Approved reviews --}}
                                @forelse ($reviews as $review)
                                    <div class="mb-4 pb-3 border-bottom border-translucent">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1">
                                                @for ($i = 1; $i <= 5; $i++)<span class="fa{{ $i <= $review->rating ? 's' : 'r' }} fa-star text-warning"></span>@endfor
                                                <span class="text-body-secondary ms-1 fw-normal">by {{ $review->user?->name ?? 'Customer' }}</span>
                                            </h6>
                                            <span class="badge badge-phoenix badge-phoenix-success fs-10"><span class="fas fa-circle-check me-1"></span>Verified</span>
                                        </div>
                                        <p class="text-body-tertiary fs-10 mb-1">{{ $review->created_at->diffForHumans() }}</p>
                                        @if ($review->title)<p class="fw-semibold mb-1">{{ $review->title }}</p>@endif
                                        <p class="text-body-highlight mb-0">{{ $review->body }}</p>
                                    </div>
                                @empty
                                    <p class="text-body-tertiary mb-0">No reviews yet. Purchased this product? Share your experience.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <x-product-slider title="Related products" subtitle="More products from the same category" :products="$related" />

    <x-product-slider title="Recently viewed" subtitle="Pick up where you left off" :products="$recentlyViewed" class="py-0 mb-9" />
@endsection

@push('scripts')
    <script type="module">
        import PhotoSwipeLightbox from 'https://cdn.jsdelivr.net/npm/photoswipe@5.4.3/dist/photoswipe-lightbox.esm.min.js';
        import PhotoSwipe from 'https://cdn.jsdelivr.net/npm/photoswipe@5.4.3/dist/photoswipe.esm.min.js';

        (function () {
            // -- PhotoSwipe Gallery Logic --
            var main = document.getElementById('pd-main-image');
            var currentIndex = 0;
            
            var pswpItems = [];
            @if ($gallery->count() > 0)
                @foreach ($gallery as $media)
                    pswpItems.push({
                        src: '{{ $media->getUrl() }}',
                        width: 1600, // Pre-warmed default
                        height: 1600, 
                        alt: '{{ addslashes($product->name) }}'
                    });
                @endforeach
            @else
                pswpItems.push({
                    src: '{{ $product->imageUrl() }}',
                    width: 1600,
                    height: 1600,
                    alt: '{{ addslashes($product->name) }}'
                });
            @endif

            // Asynchronously fetch natural dimensions to ensure perfect aspect ratio
            pswpItems.forEach(function(item) {
                var img = new Image();
                img.onload = function() {
                    item.width = img.naturalWidth || 1600;
                    item.height = img.naturalHeight || 1600;
                };
                img.src = item.src;
            });

            document.querySelectorAll('.pd-thumb').forEach(function (btn, index) {
                btn.addEventListener('click', function () {
                    main.src = btn.getAttribute('data-full');
                    currentIndex = index;
                    document.querySelectorAll('.pd-thumb').forEach(function (b) {
                        b.classList.remove('border-primary');
                        b.classList.add('border-translucent');
                    });
                    btn.classList.remove('border-translucent');
                    btn.classList.add('border-primary');
                });
            });

            var lightbox = new PhotoSwipeLightbox({
                dataSource: pswpItems,
                pswpModule: PhotoSwipe,
                bgOpacity: 0.95,
                wheelToZoom: true,
                paddingFn: (viewportSize) => {
                    return { top: 30, bottom: 30, left: 20, right: 20 };
                }
            });

            lightbox.on('uiRegister', function() {
                lightbox.pswp.ui.registerElement({
                    name: 'info-btn',
                    order: 9,
                    isButton: true,
                    tagName: 'button',
                    html: '<svg aria-hidden="true" class="pswp__icn" viewBox="0 0 32 32" width="32" height="32"><path d="M16 4a12 12 0 1 0 12 12A12 12 0 0 0 16 4zm0 22a10 10 0 1 1 10-10 10 10 0 0 1-10 10z"/><path d="M16 11a1.5 1.5 0 1 0 1.5 1.5A1.5 1.5 0 0 0 16 11zM15 15h2v7h-2z"/></svg>',
                    onClick: (event, el) => {
                        var panel = document.getElementById('pswp-info-panel');
                        if (panel) {
                            panel.classList.toggle('open');
                        }
                    }
                });
            });

            lightbox.on('beforeOpen', () => {
                if (!document.getElementById('pswp-info-panel')) {
                    var panel = document.createElement('div');
                    panel.id = 'pswp-info-panel';
                    panel.className = 'pswp-info-panel';
                    
                    var desc = @json(strip_tags($product->description));
                    var productName = @json($product->name);
                    var sku = document.getElementById('pd-sku') ? document.getElementById('pd-sku').textContent : '{{ $sel['sku'] ?: $selected?->sku }}';
                    
                    var html = `
                        <div class="pswp-info-panel-content">
                            <button class="pswp-info-close" aria-label="Close panel" onclick="document.getElementById('pswp-info-panel').classList.remove('open')">&times;</button>
                            <h3 class="mb-2 fw-bold" style="color: inherit;">${productName}</h3>
                            <p class="fs-8 mb-3 fw-semibold" style="opacity: 0.8; color: inherit;">SKU: <span id="pswp-panel-sku">${sku}</span></p>
                            <div class="mb-4" style="font-size: 1rem; line-height: 1.6; opacity: 0.9; color: inherit; white-space: pre-wrap;">${desc}</div>
                    `;

                    @if ($product->specifications->isNotEmpty())
                        html += `
                            <hr class="my-4" style="border-color: currentColor; opacity: 0.15;" />
                            <h4 class="mb-3 fw-bold" style="color: inherit;">Specifications</h4>
                            <div class="table-responsive mb-2">
                                <table class="table table-sm border-0 mb-0" style="color: inherit; --bs-table-bg: transparent; --bs-table-color: inherit; border-color: rgba(128,128,128,0.2) !important;">
                                    <tbody>
                                        @foreach ($product->specifications as $spec)
                                            <tr>
                                                <td class="fw-semibold align-middle px-3 py-2" style="width: 40%; background: rgba(128,128,128,0.05); border-color: rgba(128,128,128,0.2); color: inherit;">{{ addslashes($spec->label) }}</td>
                                                <td class="px-3 py-2" style="border-color: rgba(128,128,128,0.2); color: inherit; opacity: 0.9;">{{ addslashes($spec->value) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        `;
                    @endif
                    
                    html += `</div>`;
                    panel.innerHTML = html;
                    document.body.appendChild(panel);
                }
            });

            lightbox.on('close', () => {
                var panel = document.getElementById('pswp-info-panel');
                if (panel) panel.classList.remove('open');
            });

            lightbox.on('destroy', () => {
                var panel = document.getElementById('pswp-info-panel');
                if (panel) panel.remove();
            });

            lightbox.init();

            main.addEventListener('click', function() {
                lightbox.loadAndOpen(currentIndex);
            });

            // -- Quantity & Variant Logic --
            var data = @json($variantData);
            var select = document.getElementById('pd-variant-select');
            
            function money(kobo) { return '₦' + (Number(kobo) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

            var qtyInput = document.getElementById('pd-qty');
            var hiddenQty = document.getElementById('pd-hidden-qty');
            var btnMinus = document.getElementById('pd-qty-minus');
            var btnPlus = document.getElementById('pd-qty-plus');

            var hintEl = document.getElementById('pd-qty-hint');
            var hintText = document.getElementById('pd-qty-hint-text');

            // Clamp the input to [1, stock], keep the hidden field in sync, and
            // reflect the ceiling: disable the "+" button and reveal a subtle hint
            // once the shopper reaches the available stock.
            function reflectMax() {
                var max = parseInt(qtyInput.max) || 1;
                var val = Math.max(1, Math.min(parseInt(qtyInput.value) || 1, max));
                qtyInput.value = val;
                hiddenQty.value = val;

                var atMax = val >= max;
                btnPlus.disabled = atMax;
                btnPlus.classList.toggle('disabled', atMax);

                if (hintEl) {
                    hintEl.classList.toggle('d-none', !atMax);
                    if (atMax && hintText) {
                        hintText.textContent = 'Only ' + max + ' in stock — that’s the most you can add right now.';
                    }
                }
            }

            function updateQtyUI(qty, maxQty, inCart) {
                qtyInput.value = Math.max(1, Math.min(qty, maxQty));
                hiddenQty.value = qtyInput.value;
                document.getElementById('pd-add-text').textContent = inCart ? 'Update cart' : 'Add to cart';
                reflectMax();
            }

            btnMinus.addEventListener('click', function(e) {
                // Theme JS handles the actual decrement, we just sync + reflect.
                setTimeout(reflectMax, 10);
            });

            btnPlus.addEventListener('click', function(e) {
                // Theme JS handles the actual increment, we just sync + cap.
                setTimeout(reflectMax, 10);
            });

            qtyInput.addEventListener('change', reflectMax);

            reflectMax();

            if (select) {
                select.addEventListener('change', function () {
                    var v = data[this.value];
                    if (!v) return;
                    document.getElementById('pd-variant-id').value = this.value;
                    document.getElementById('pd-sku').textContent = v.sku;
                    
                    var panelSku = document.getElementById('pswp-panel-sku');
                    if (panelSku) { panelSku.textContent = v.sku; }
                    
                    var priceEl = document.getElementById('pd-price');
                    priceEl.textContent = money(v.unit);
                    priceEl.classList.toggle('text-danger', v.hasDiscount);
                    priceEl.classList.toggle('text-body-emphasis', !v.hasDiscount);
                    
                    var oldEl = document.getElementById('pd-old');
                    if(oldEl) {
                        oldEl.textContent = money(v.retail);
                        oldEl.classList.toggle('d-none', !v.hasDiscount);
                    }
                    
                    var badgeEl = document.getElementById('pd-discount-badge');
                    if(badgeEl) {
                        var dp = v.retail > 0 ? Math.round(((v.retail - v.unit) / v.retail) * 100) : 0;
                        badgeEl.textContent = dp + '% off';
                        badgeEl.classList.toggle('d-none', !v.hasDiscount);
                    }
                    
                    var stockEl = document.getElementById('pd-stock');
                    stockEl.textContent = v.stock > 0 ? 'In stock (' + v.stock + ' available)' : 'Out of stock';
                    stockEl.classList.toggle('text-success', v.stock > 0);
                    stockEl.classList.toggle('text-danger', v.stock < 1);
                    
                    qtyInput.max = Math.max(1, v.stock);
                    updateQtyUI(v.cartQty > 0 ? v.cartQty : 1, v.stock, v.cartQty > 0);

                    document.getElementById('pd-add').disabled = v.stock < 1;

                    // Keep the demand-capture widgets pointed at the current variant.
                    var bisWrap = document.getElementById('pd-back-in-stock');
                    var bisVariant = document.getElementById('pd-bis-variant');
                    var bulkVariant = document.getElementById('pd-bulk-variant');
                    var bulkMax = document.getElementById('pd-bulk-max');
                    if (bisWrap) { bisWrap.classList.toggle('d-none', v.stock > 0); }
                    if (bisVariant) { bisVariant.value = this.value; }
                    if (bulkVariant) { bulkVariant.value = this.value; }
                    if (bulkMax) { bulkMax.textContent = Math.max(1, v.stock); }
                });
            }
            
            // Wishlist hearts (main + related) are handled by the global
            // unified wishlist module in partials/wishlist-script.blade.php.
        })();
    </script>
@endpush