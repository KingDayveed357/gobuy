<section class="bg-body-highlight pt-7 pb-5 mt-9 border-top border-translucent">
    <div class="container-small">
        <div class="row g-4 justify-content-between">
            <div class="col-6 col-md-3">
                <h5 class="text-primary mb-3">gobuy</h5>
                <p class="fs-9 text-body-tertiary mb-0">Retail &amp; wholesale, locally stocked and delivered fast across Nigeria.</p>
            </div>
            <div class="col-6 col-md-2">
                <h6 class="mb-3">Shop</h6>
                <ul class="list-unstyled fs-9">
                    <li class="mb-2"><a class="text-body-tertiary text-decoration-none" href="{{ route('products.index') }}">All products</a></li>
                    <li class="mb-2"><a class="text-body-tertiary text-decoration-none" href="{{ route('cart.index') }}">Cart</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-2">
                <h6 class="mb-3">Categories</h6>
                <ul class="list-unstyled fs-9">
                    @foreach ($navCategories ?? [] as $category)
                        <li class="mb-2"><a class="text-body-tertiary text-decoration-none" href="{{ route('products.index', ['category' => $category->slug]) }}">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-md-3">
                <h6 class="mb-3">Stay updated</h6>
                <div class="input-group">
                    <input class="form-control form-control-sm" type="email" placeholder="Email address">
                    <button class="btn btn-sm btn-primary" type="button">Subscribe</button>
                </div>
            </div>
        </div>
    </div>
</section>
<footer class="footer position-relative">
    <div class="container-small">
        <div class="row g-0 justify-content-between align-items-center h-100 py-3">
            <div class="col-12 col-sm-auto text-center">
                <p class="mb-0 text-body">&copy; {{ date('Y') }} gobuy. All rights reserved.</p>
            </div>
            <div class="col-12 col-sm-auto text-center">
                <p class="mb-0 text-body-tertiary text-opacity-85">Built for speed.</p>
            </div>
        </div>
    </div>
</footer>
