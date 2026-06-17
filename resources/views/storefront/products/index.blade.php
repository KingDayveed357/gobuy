@extends('layouts.storefront')

@section('title', 'Products — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <form method="GET" action="{{ route('products.index') }}" class="product-listing-shell">
                <div class="d-flex justify-content-between align-items-center mb-4 d-lg-none">
                    <p class="text-body-tertiary mb-0">{{ $products->total() }} product(s)</p>
                    <button class="btn btn-sm btn-phoenix-secondary text-body-tertiary" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#productFilterDrawer" aria-controls="productFilterDrawer">
                        <span class="fa-solid fa-filter me-2"></span>Filter
                    </button>
                </div>

                <input type="hidden" name="sort" value="{{ request('sort') }}">

                <div class="offcanvas-lg offcanvas-start product-filter-drawer" tabindex="-1" id="productFilterDrawer" aria-labelledby="productFilterDrawerLabel">
                    <div class="offcanvas-header d-lg-none border-bottom border-translucent">
                        <h3 class="offcanvas-title mb-0" id="productFilterDrawerLabel">Filters</h3>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body p-0">
                        <aside class="product-filter-panel bg-body scrollbar">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="mb-0">Filters</h3>
                                @if (request()->hasAny(['q', 'category', 'in_stock', 'min', 'max']))
                                    <a class="btn btn-link p-0 fs-9" href="{{ route('products.index') }}">Clear all</a>
                                @endif
                            </div>

                            <div class="product-filter-section">
                                <div class="fs-8 text-body-highlight mb-2">Search</div>
                                <input class="form-control form-control-sm" type="search" name="q"
                                       value="{{ request('q') }}" placeholder="Search products">
                            </div>

                            <div class="product-filter-section">
                                <div class="fs-8 text-body-highlight mb-2">Category</div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="radio" name="category" value="" id="cat-all"
                                           @checked(! request('category'))>
                                    <label class="form-check-label fs-8 text-body" for="cat-all">All products</label>
                                </div>
                                @foreach ($categories as $category)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="radio" name="category" value="{{ $category->slug }}"
                                               id="cat-{{ $category->id }}" @checked(request('category') === $category->slug)>
                                        <label class="form-check-label fs-8 text-body" for="cat-{{ $category->id }}">{{ $category->name }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="product-filter-section">
                                <div class="fs-8 text-body-highlight mb-2">Availability</div>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="in_stock" value="1"
                                           id="in-stock" @checked(request('in_stock'))>
                                    <label class="form-check-label fs-8 text-body" for="in-stock">In stock only</label>
                                </div>
                            </div>

                            <div class="product-filter-section">
                                <div class="fs-8 text-body-highlight mb-2">Price range (₦)</div>
                                <div class="input-group input-group-sm">
                                    <input class="form-control" type="number" name="min" min="0" value="{{ request('min') }}" placeholder="Min">
                                    <input class="form-control" type="number" name="max" min="0" value="{{ request('max') }}" placeholder="Max">
                                </div>
                            </div>

                            <button class="btn btn-primary btn-sm w-100" type="submit" data-bs-dismiss="offcanvas">Apply filters</button>
                        </aside>
                    </div>
                </div>

                <div class="product-results-panel">
                    <div class="d-none d-lg-flex flex-between-center mb-4">
                        <p class="text-body-tertiary mb-0">{{ $products->total() }} product(s)</p>
                        <select name="sort" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            <option value="">Newest</option>
                            <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                            <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                            <option value="name" @selected(request('sort') === 'name')>Name</option>
                        </select>
                    </div>

                    <div class="product-results-grid">
                        <div class="row gx-4 gy-6">
                            @forelse ($products as $product)
                                <div class="col-6 col-md-4 col-xxl-3">
                                    <x-product-card :product="$product" />
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="card border border-translucent bg-body-emphasis">
                                        <div class="card-body text-center py-6">
                                            <p class="text-body-tertiary mb-0">No products match your filters.</p>
                                        </div>
                                    </div>
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-5">{{ $products->links() }}</div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
