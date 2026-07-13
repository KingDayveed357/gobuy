<section class="bg-body-highlight pt-7 pb-5 mt-9 border-top border-translucent">
    <div class="container-small">
        @php
            $footerCategories = collect($navCategories ?? [])->take(6);
            $hasMoreCategories = collect($navCategories ?? [])->count() > $footerCategories->count();
        @endphp
        <div class="row g-4 justify-content-between align-items-start">
            <div class="col-12 col-md-4 col-lg-3">
                <a href="{{ route('home') }}" class="d-inline-block mb-3" aria-label="Quintessential Mart — home">
                    <x-brand-logo class="text-primary gb-footer-brand" :size="40" :compact="false" />
                </a>
                <p class="fs-9 text-body-tertiary mb-3 gb-footer-brand-copy">Retail &amp; wholesale, locally stocked and delivered fast across Nigeria.</p>
                @php($socials = array_filter(['instagram' => setting('instagram_url'), 'facebook' => setting('facebook_url'), 'x-twitter' => setting('x_url')]))
                @if (! empty($socials))
                    <div class="d-flex gap-2">
                        @foreach ($socials as $icon => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-phoenix-secondary" aria-label="{{ $icon }}"><span class="fab fa-{{ $icon }}"></span></a>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <h6 class="mb-3">Shop</h6>
                <ul class="list-unstyled fs-9">
                    <li class="mb-2"><a class="text-body-tertiary text-decoration-none" href="{{ route('products.index') }}">All products</a></li>
                    <li class="mb-2"><a class="text-body-tertiary text-decoration-none" href="{{ route('cart.index') }}">Cart</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3 col-lg-3">
                <h6 class="mb-3">Categories</h6>
                <ul class="list-unstyled fs-9 gb-footer-categories">
                    @foreach ($footerCategories as $category)
                        <li class="mb-2"><a class="text-body-tertiary text-decoration-none" href="{{ route('products.index', ['category' => $category->slug]) }}">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
                @if ($hasMoreCategories)
                    <a class="text-primary text-decoration-none fs-9 fw-semibold" href="{{ route('products.index') }}">View all categories</a>
                @endif
            </div>
            <div class="col-12 col-md-12 col-lg-4">
                <h6 class="mb-3">Stay updated</h6>
                <form method="POST" action="{{ route('newsletter.store') }}" class="gb-footer-subscribe">
                    @csrf
                    <div class="gb-footer-subscribe__group">
                        <input class="form-control" type="email" name="email" required placeholder="Email address">
                        <button class="btn btn-primary" type="submit">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<footer class="footer position-relative">
    <div class="container-small">
        <div class="row g-0 justify-content-between align-items-center h-100 py-3">
            <div class="col-12 col-sm-auto text-center">
                <p class="mb-0 text-body">&copy; {{ date('Y') }} Quintessential Mart. All rights reserved.</p>
            </div>
            <div class="col-12 col-sm-auto text-center">
                <p class="mb-0 text-body-tertiary text-opacity-85">Built for speed.</p>
            </div>
        </div>
    </div>
</footer>
