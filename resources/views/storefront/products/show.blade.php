@extends('layouts.storefront')

@section('title', $product->name.' — gobuy')

@section('content')
    <section class="py-0 pt-5">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('products.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
                </ol>
            </nav>

            <div class="row g-5 mb-5 mb-lg-8">
                <div class="col-12 col-lg-6">
                    <div class="d-flex align-items-center justify-content-center border border-translucent rounded-3 text-center p-5 mb-3" style="min-height: 360px;">
                        <img class="img-fluid" style="max-height: 360px; object-fit: contain;"
                             src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">
                    </div>
                    @if ($product->images->count() > 1)
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach ($product->images as $image)
                                <div class="border border-translucent rounded-2 p-2" style="width: 64px;">
                                    <img class="img-fluid" src="{{ asset($image->path) }}" alt="{{ $image->alt }}">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-12 col-lg-6">
                    <div class="d-flex flex-column justify-content-between h-100">
                        <div>
                            <div class="d-flex flex-wrap align-items-center mb-2">
                                <div class="me-2">
                                    @for ($i = 0; $i < 5; $i++)
                                        <span class="fa fa-star text-warning"></span>
                                    @endfor
                                </div>
                                <p class="text-primary fw-semibold mb-0 fs-9">Highly rated by buyers</p>
                            </div>
                            <h3 class="mb-3 lh-sm">{{ $product->name }}</h3>
                            <div class="d-flex flex-wrap align-items-start mb-3">
                                @if ($product->is_featured)
                                    <span class="badge text-bg-success fs-9 rounded-pill me-2 fw-semibold">Featured</span>
                                @endif
                                <span class="text-body-tertiary fs-9">SKU: {{ $product->sku }}</span>
                            </div>

                            <div class="mb-2">
                                <x-price-tag :product="$product" />
                            </div>

                            @if ($product->isInStock())
                                <p class="text-success fw-semibold fs-7 mb-3">In stock ({{ $product->stock }} available)</p>
                            @else
                                <p class="text-danger fw-semibold fs-7 mb-3">Out of stock</p>
                            @endif

                            @auth
                                @if (auth()->user()->isWholesale() && $product->wholesale_price)
                                    <div class="alert alert-subtle-info fs-9 py-2">
                                        Wholesale price applies automatically on {{ $product->wholesale_min_qty }}+ units.
                                    </div>
                                @endif
                            @endauth

                            <p class="text-body-secondary mb-4">{{ \Illuminate\Support\Str::limit($product->description, 280) }}</p>
                        </div>

                        <div>
                            <form action="{{ route('cart.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <div class="row g-3 align-items-end">
                                    <div class="col-auto">
                                        <p class="fw-semibold mb-2 text-body">Quantity</p>
                                        <input class="form-control text-center" type="number" name="quantity" value="1"
                                               min="1" max="{{ $product->stock }}" style="width: 90px;" @disabled(! $product->isInStock())>
                                    </div>
                                    <div class="col">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-lg btn-warning rounded-pill w-100 fs-9 fs-sm-8" @disabled(! $product->isInStock())>
                                                <span class="fas fa-shopping-cart me-2"></span>Add to cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-underline fs-9 mb-4" id="productTab" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-description" role="tab">Description</a></li>
            </ul>
            <div class="tab-content mb-7" id="productTabContent">
                <div class="tab-pane fade show active text-body-emphasis" id="tab-description" role="tabpanel">
                    <p class="mb-0">{{ $product->description }}</p>
                </div>
            </div>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="py-0 mb-9">
            <div class="container-small">
                <h3 class="mb-4">Related products</h3>
                <div class="row gx-3 gy-5">
                    @foreach ($related as $item)
                        <div class="col-6 col-md-4 col-lg-3 col-xxl-2">
                            <x-product-card :product="$item" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
