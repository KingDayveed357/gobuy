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
                                @push('scripts')
                                    <script>
                                        (function () {
                                            var main = document.getElementById('pd-main-image');
                                            document.querySelectorAll('.pd-thumb').forEach(function (btn) {
                                                btn.addEventListener('click', function () {
                                                    main.src = btn.getAttribute('data-full');
                                                    document.querySelectorAll('.pd-thumb').forEach(function (b) {
                                                        b.classList.remove('border-primary');
                                                        b.classList.add('border-translucent');
                                                    });
                                                    btn.classList.remove('border-translucent');
                                                    btn.classList.add('border-primary');
                                                });
                                            });
                                        })();
                                    </script>
                                @endpush
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
    <script>
        (function () {
            // -- Quantity & Variant Logic --
            var data = @json($variantData);
            var select = document.getElementById('pd-variant-select');
            
            function money(kobo) { return '₦' + (Number(kobo) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

            var qtyInput = document.getElementById('pd-qty');
            var hiddenQty = document.getElementById('pd-hidden-qty');
            var btnMinus = document.getElementById('pd-qty-minus');
            var btnPlus = document.getElementById('pd-qty-plus');

            function updateQtyUI(qty, maxQty, inCart) {
                qtyInput.value = Math.max(1, Math.min(qty, maxQty));
                hiddenQty.value = qtyInput.value;
                document.getElementById('pd-add-text').textContent = inCart ? 'Update cart' : 'Add to cart';
            }

            btnMinus.addEventListener('click', function(e) {
                // Theme JS handles the actual decrement, we just sync the hidden input.
                setTimeout(function() {
                    hiddenQty.value = qtyInput.value;
                }, 10);
            });

            btnPlus.addEventListener('click', function(e) {
                // Theme JS handles the actual increment, we just sync the hidden input.
                setTimeout(function() {
                    hiddenQty.value = qtyInput.value;
                }, 10);
            });

            qtyInput.addEventListener('change', function() {
                var max = parseInt(qtyInput.max) || 1;
                qtyInput.value = Math.max(1, Math.min(parseInt(qtyInput.value) || 1, max));
                hiddenQty.value = qtyInput.value;
            });

            if (select) {
                select.addEventListener('change', function () {
                    var v = data[this.value];
                    if (!v) return;
                    document.getElementById('pd-variant-id').value = this.value;
                    document.getElementById('pd-sku').textContent = v.sku;
                    
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
                });
            }
            
            // Wishlist hearts (main + related) are handled by the global
            // unified wishlist module in partials/wishlist-script.blade.php.
        })();
    </script>
@endpush