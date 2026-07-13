@extends('layouts.storefront')

@section('title', 'Products — Quintessential Mart')

@php
    $hasFilters = request()->hasAny(['q', 'category', 'brand', 'in_stock', 'min', 'max']);
@endphp

@section('content')
    <section class="pt-5 pb-9">
        <div class="product-filter-container">
            <button class="btn btn-sm btn-phoenix-secondary text-body-tertiary mb-5 d-lg-none" data-phoenix-toggle="offcanvas" data-phoenix-target="#productFilterColumn" type="button">
                <span class="fa-solid fa-filter me-2"></span>Filter
            </button>

            <div class="row">
                <div class="col-lg-3 col-xxl-2 ps-2 ps-xxl-3">
                    <form method="GET" action="{{ route('products.index') }}" class="phoenix-offcanvas-filter bg-body scrollbar phoenix-offcanvas phoenix-offcanvas-fixed" id="productFilterColumn" style="top: 92px" data-breakpoint="lg">
                        <input type="hidden" name="sort" value="{{ request('sort') }}">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Filters</h3>
                            <button class="btn d-lg-none p-0" type="button" data-phoenix-dismiss="offcanvas" aria-label="Close filters">
                                <span class="fas fa-times fs-8"></span>
                            </button>
                        </div>

                        @if ($hasFilters)
                            <a class="btn btn-link px-0 pt-0 fs-9 fw-semibold" href="{{ route('products.index') }}">Clear all filters</a>
                        @endif

                        <a class="btn px-0 d-block collapse-indicator" data-bs-toggle="collapse" href="#collapseSearch" role="button" aria-expanded="true" aria-controls="collapseSearch">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div class="fs-8 text-body-highlight">Search</div><span class="fa-solid fa-angle-down toggle-icon text-body-quaternary"></span>
                            </div>
                        </a>
                        <div class="collapse show" id="collapseSearch">
                            <div class="mb-2">
                                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search products">
                            </div>
                        </div>

                        <a class="btn px-0 d-block collapse-indicator" data-bs-toggle="collapse" href="#collapseAvailability" role="button" aria-expanded="true" aria-controls="collapseAvailability">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div class="fs-8 text-body-highlight">Availability</div><span class="fa-solid fa-angle-down toggle-icon text-body-quaternary"></span>
                            </div>
                        </a>
                        <div class="collapse show" id="collapseAvailability">
                            <div class="mb-2">
                                <div class="form-check mb-0">
                                    <input class="form-check-input mt-0" id="inStockInput" type="checkbox" name="in_stock" value="1" @checked(request('in_stock'))>
                                    <label class="form-check-label d-block lh-sm fs-8 text-body fw-normal mb-0" for="inStockInput">In stock</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input mt-0" id="allStockInput" type="checkbox" @checked(! request('in_stock')) disabled>
                                    <label class="form-check-label d-block lh-sm fs-8 text-body fw-normal mb-0" for="allStockInput">All availability</label>
                                </div>
                            </div>
                        </div>

                        <a class="btn px-0 d-block collapse-indicator" data-bs-toggle="collapse" href="#collapseBrands" role="button" aria-expanded="true" aria-controls="collapseBrands">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div class="fs-8 text-body-highlight">Category</div><span class="fa-solid fa-angle-down toggle-icon text-body-quaternary"></span>
                            </div>
                        </a>
                        <div class="collapse show" id="collapseBrands">
                            <div class="mb-2">
                                <div class="form-check mb-0">
                                    <input class="form-check-input mt-0" id="cat-all" type="radio" name="category" value="" @checked(! request('category'))>
                                    <label class="form-check-label d-block lh-sm fs-8 text-body fw-normal mb-0" for="cat-all">All products</label>
                                </div>
                                @foreach ($categories as $category)
                                    <div class="form-check mb-0">
                                        <input class="form-check-input mt-0" id="cat-{{ $category->id }}" type="radio" name="category" value="{{ $category->slug }}" @checked(request('category') === $category->slug)>
                                        <label class="form-check-label d-block lh-sm fs-8 text-body fw-normal mb-0" for="cat-{{ $category->id }}">{{ $category->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <a class="btn px-0 d-block collapse-indicator" data-bs-toggle="collapse" href="#collapsePriceRange" role="button" aria-expanded="true" aria-controls="collapsePriceRange">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div class="fs-8 text-body-highlight">Price range</div><span class="fa-solid fa-angle-down toggle-icon text-body-quaternary"></span>
                            </div>
                        </a>
                        <div class="collapse show" id="collapsePriceRange">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="input-group me-2">
                                    <input class="form-control" type="number" name="min" min="0" value="{{ request('min') }}" placeholder="Min" aria-label="Minimum price">
                                    <input class="form-control" type="number" name="max" min="0" value="{{ request('max') }}" placeholder="Max" aria-label="Maximum price">
                                </div>
                                <button class="btn btn-phoenix-primary px-3" type="submit">Go</button>
                            </div>
                        </div>

                        @if ($brands->isNotEmpty())
                            <a class="btn px-0 y-4 d-block collapse-indicator" data-bs-toggle="collapse" href="#collapseBrand" role="button" aria-expanded="true" aria-controls="collapseBrand">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <div class="fs-8 text-body-highlight">Brand</div><span class="fa-solid fa-angle-down toggle-icon text-body-quaternary"></span>
                                </div>
                            </a>
                            <div class="collapse show" id="collapseBrand">
                                <div class="d-flex flex-column gap-2 mb-3" style="max-height: 220px; overflow-y: auto;">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input mt-0" id="brand-all" type="radio" name="brand" value="" @checked(! request('brand'))>
                                        <label class="form-check-label d-block lh-sm fs-8 text-body fw-normal mb-0" for="brand-all">All brands</label>
                                    </div>
                                    @foreach ($brands as $brand)
                                        <div class="form-check mb-0">
                                            <input class="form-check-input mt-0" id="brand-{{ $brand->id }}" type="radio" name="brand" value="{{ $brand->slug }}" @checked(request('brand') === $brand->slug)>
                                            <label class="form-check-label d-block lh-sm fs-8 text-body fw-normal mb-0" for="brand-{{ $brand->id }}">{{ $brand->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <a class="btn px-0 y-4 d-block collapse-indicator" data-bs-toggle="collapse" href="#collapseRating" role="button" aria-expanded="true" aria-controls="collapseRating">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div class="fs-8 text-body-highlight">Rating</div><span class="fa-solid fa-angle-down toggle-icon text-body-quaternary"></span>
                            </div>
                        </a>
                        <div class="collapse show" id="collapseRating">
                            @for ($rating = 5; $rating >= 1; $rating--)
                                <div class="d-flex align-items-center mb-1">
                                    <input class="form-check-input me-3" id="rating{{ $rating }}" type="radio" disabled>
                                    @for ($star = 1; $star <= 5; $star++)
                                        <span class="{{ $star <= $rating ? 'fa' : 'fa-regular' }} fa-star {{ $star <= $rating ? 'text-warning' : 'text-warning-light' }} fs-9 me-1" @if ($star > $rating) data-bs-theme="light" @endif></span>
                                    @endfor
                                    @if ($rating < 5)
                                        <p class="ms-1 mb-0">&amp; above</p>
                                    @endif
                                </div>
                            @endfor
                        </div>

                        <button class="btn btn-primary w-100 mt-3" type="submit">
                            <span class="fa-solid fa-check me-2"></span>Apply filters
                        </button>
                    </form>
                    <div class="phoenix-offcanvas-backdrop d-lg-none" data-phoenix-backdrop="" style="top: 92px"></div>
                </div>

                <div class="col-lg-9 col-xxl-10">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb mb-0 fs-9">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Products</li>
                        </ol>
                    </nav>

                    @if (! empty($activeFilters))
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                            <span class="fs-9 text-body-tertiary me-1">Filters:</span>
                            @foreach ($activeFilters as $filter)
                                <a href="{{ $filter['remove_url'] }}" class="badge badge-phoenix badge-phoenix-secondary text-decoration-none d-inline-flex align-items-center">
                                    {{ $filter['label'] }}<span class="fas fa-xmark ms-2"></span>
                                </a>
                            @endforeach
                            <a href="{{ route('products.index') }}" class="fs-9 fw-semibold text-danger text-decoration-none ms-1">Clear all</a>
                        </div>
                    @endif

                    <div class="d-flex flex-between-center mb-4">
                        <p class="text-body-tertiary mb-0">{{ $products->total() }} product(s)</p>
                        <form method="GET" action="{{ route('products.index') }}">
                            @foreach (request()->except('sort', 'page') as $key => $value)
                                @if (is_array($value))
                                    @foreach ($value as $item)
                                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <select name="sort" class="form-select form-select-sm w-auto" onchange="this.form.submit()" aria-label="Sort products">
                                <option value="">Newest</option>
                                <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                                <option value="name" @selected(request('sort') === 'name')>Name</option>
                            </select>
                        </form>
                    </div>

                    @if ($products->isEmpty())
                        <div class="card border border-translucent bg-body-emphasis mb-8">
                            <div class="card-body text-center py-6">
                                <p class="text-body-tertiary mb-0">No products match your filters.</p>
                            </div>
                        </div>
                    @else
                        {{-- Fluid grid: adapts to the width left by the filter sidebar
                             (~2 cards on phones up to ~5–6 on wide). See gobuy.css. --}}
                        <div class="gb-product-grid product-listing-grid mb-8">
                            @foreach ($products as $product)
                                <x-product-card :product="$product" />
                            @endforeach
                        </div>
                    @endif

                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
