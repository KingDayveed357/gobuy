@extends('layouts.storefront')

@section('title', 'gobuy — Retail & Wholesale, delivered fast')

@php
    // Resolve a fitting Font Awesome icon for a category: exact slug first,
    // then a keyword match on the name, then a sensible default.
    $iconFor = function ($category) {
        $bySlug = [
            'electronics' => 'fa-laptop', 'phones' => 'fa-mobile-screen-button', 'fashion' => 'fa-shirt',
            'home-kitchen' => 'fa-house', 'groceries' => 'fa-basket-shopping', 'gifts' => 'fa-gift',
        ];
        if (isset($bySlug[$category->slug])) {
            return $bySlug[$category->slug];
        }
        $name = \Illuminate\Support\Str::lower($category->name);
        $byKeyword = [
            'phone' => 'fa-mobile-screen-button', 'laptop' => 'fa-laptop', 'computer' => 'fa-desktop',
            'electronic' => 'fa-plug', 'fashion' => 'fa-shirt', 'cloth' => 'fa-shirt', 'shoe' => 'fa-shoe-prints',
            'home' => 'fa-house', 'kitchen' => 'fa-utensils', 'grocer' => 'fa-basket-shopping', 'food' => 'fa-utensils',
            'beauty' => 'fa-spa', 'health' => 'fa-heart-pulse', 'safety' => 'fa-helmet-safety', 'tool' => 'fa-screwdriver-wrench',
            'sport' => 'fa-futbol', 'toy' => 'fa-puzzle-piece', 'book' => 'fa-book', 'car' => 'fa-car', 'auto' => 'fa-car',
            'baby' => 'fa-baby', 'furniture' => 'fa-couch', 'gift' => 'fa-gift', 'watch' => 'fa-clock', 'game' => 'fa-gamepad',
        ];
        foreach ($byKeyword as $kw => $icon) {
            if (str_contains($name, $kw)) {
                return $icon;
            }
        }
        return 'fa-tags';
    };
    $dealsSwiper = '{"slidesPerView":2,"spaceBetween":12,"breakpoints":{"768":{"slidesPerView":3,"spaceBetween":16},"992":{"slidesPerView":4,"spaceBetween":16},"1200":{"slidesPerView":5,"spaceBetween":16}}}';
@endphp

@section('content')
    <div class="ecommerce-homepage pt-5 mb-9">
        <section class="py-0 px-xl-3">
            <div class="container px-xl-0 px-xxl-3">
                      {{-- Categories --}}
                @if ($categories->isNotEmpty())
                    <div class="d-flex flex-between-center mb-2">
                        <h3 class="mb-0">Shop by category</h3>
                        <a class="btn btn-link btn-sm p-0 d-none d-md-block" href="{{ route('products.index') }}">View all<span class="fas fa-chevron-right fs-10 ms-1"></span></a>
                    </div>
                    <div class="gb-category-strip mb-7">
                        @foreach ($categories as $category)
                            <a class="gb-category-item" href="{{ route('products.index', ['category' => $category->slug]) }}">
                                <div class="gb-category-tile">
                                    <span class="fas {{ $iconFor($category) }}"></span>
                                </div>
                                <p class="gb-category-label text-nowrap">{{ $category->name }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Admin-managed hero banners (layout-aware, responsive, scheduled) --}}
                @if (isset($heroBanners) && $heroBanners->isNotEmpty())
                    <div class="row g-3 mb-7">
                        @foreach ($heroBanners as $banner)
                            @php($col = match ($banner->layout) {
                                'grid' => 'col-12 col-md-6 col-lg-4',
                                'split' => 'col-12 col-lg-6',
                                default => 'col-12',
                            })
                            <div class="{{ $col }}">
                                <x-banner :banner="$banner" class="h-100" />
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Banners --}}
                <!-- <div class="row g-3 mb-6">
                    <div class="col-12">
                        <div class="whooping-banner w-100 rounded-3 overflow-hidden">
                            <div class="bg-holder z-n1 product-bg" style="background-image:url({{ asset('theme/img/e-commerce/whooping_banner_product.png') }});background-position: bottom right;"></div>
                            <div class="bg-holder z-n1 shape-bg" style="background-image:url({{ asset('theme/img/e-commerce/whooping_banner_shape_2.png') }});background-position: bottom left;"></div>
                            <div class="banner-text p-5 p-md-7" data-bs-theme="light">
                                <h2 class="text-warning-light fw-bolder fs-lg-3 fs-xxl-2 mb-1">Whooping <span class="gradient-text">60%</span> Off</h2>
                                <h3 class="fw-bolder fs-lg-5 fs-xxl-3 text-white mb-3">on everyday items</h3>
                                <a class="btn btn-lg btn-primary rounded-pill" href="{{ route('products.index') }}">Shop Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-6">
                        <div class="gift-items-banner gb-banner-tall w-100 rounded-3 overflow-hidden position-relative d-flex align-items-center">
                            <div class="bg-holder z-n1" style="background-image:url({{ asset('theme/img/e-commerce/gift-items-banner-bg.png') }});"></div>
                            <div class="banner-text text-md-center p-5 w-100">
                                <h2 class="text-white fw-bolder fs-xl-4 mb-3">Get <span class="gradient-text">10% Off</span><br class="d-md-none"> on gift items</h2>
                                <a class="btn btn-lg btn-primary rounded-pill" href="{{ route('products.index') }}">Buy Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-6">
                        <div class="best-in-market-banner gb-banner-tall w-100 rounded-3 overflow-hidden position-relative d-flex align-items-center px-4 px-sm-7 px-md-9">
                            <div class="bg-holder z-n1" style="background-image:url({{ asset('theme/img/e-commerce/best-in-market-bg.png') }});"></div>
                            <div class="row align-items-center w-100 gx-2">
                                <div class="col-7">
                                    <div class="banner-text">
                                        <h2 class="text-white fw-bolder fs-4 fs-sm-3 mb-1">Best in the market</h2>
                                        <p class="text-white-50 fw-semibold mb-3">Wholesale-ready stock</p>
                                        <a class="btn btn-lg btn-warning rounded-pill" href="{{ route('products.index') }}">Buy Now</a>
                                    </div>
                                </div>
                                <div class="col-5 text-end"><img class="gb-banner-img" src="{{ asset('theme/img/e-commerce/5.png') }}" alt=""></div>
                            </div>
                        </div>
                    </div>
                </div> -->
<div class="row g-3 mb-9">
              <div class="col-12">
                <div class="whooping-banner w-100 rounded-3 overflow-hidden">
                  <div class="bg-holder z-n1 product-bg" style="background-image:url({{ asset('theme/img/e-commerce/whooping_banner_product.png') }});background-position: bottom right;"></div>
                  <!--/.bg-holder-->
                  <div class="bg-holder z-n1 shape-bg" style="background-image:url({{ asset('theme/img/e-commerce/whooping_banner_shape_2.png') }});background-position: bottom left;"></div>
                  <!--/.bg-holder-->
                  <div class="banner-text" data-bs-theme="light">
                    <h2 class="text-warning-light fw-bolder fs-lg-3 fs-xxl-2">Whooping <span class="gradient-text">60% </span>Off</h2>
                    <h3 class="fw-bolder fs-lg-5 fs-xxl-3 text-white">on everyday items</h3>
                  </div><a class="btn btn-lg btn-primary rounded-pill banner-button" href="#!">Shop Now</a>
                </div>
              </div>
              <div class="col-12 col-xl-6">
                <div class="gift-items-banner w-100 rounded-3 overflow-hidden">
                  <div class="bg-holder z-n1 banner-bg" style="background-image:url({{ asset('theme/img/e-commerce/gift-items-banner-bg.png') }});"></div>
                  <!--/.bg-holder-->
                  <div class="banner-text text-md-center">
                    <h2 class="text-white fw-bolder fs-xl-4">Get <span class="gradient-text">10% Off </span><br class="d-md-none"> on gift items</h2><a class="btn btn-lg btn-primary rounded-pill banner-button" href="#!">Buy Now</a>
                  </div>
                </div>
              </div>
              <div class="col-12 col-xl-6">
                <div class="best-in-market-banner d-flex h-100 px-4 px-sm-7 py-5 px-md-11 rounded-3 overflow-hidden">
                  <div class="bg-holder z-n1 banner-bg" style="background-image:url({{ asset('theme/img/e-commerce/best-in-market-bg.png') }});"></div>
                  <!--/.bg-holder-->
                  <div class="row align-items-center w-sm-100">
                    <div class="col-8">
                      <div class="banner-text">
                        <h2 class="text-white fw-bolder fs-sm-4 mb-5">MI 11 Pro<br><span class="fs-7 fs-sm-6"> Best in the market</span></h2><a class="btn btn-lg btn-warning rounded-pill banner-button" href="#!">Buy Now</a>
                      </div>
                    </div>
                    <div class="col-4"><img class="w-100 w-sm-75" src="{{ asset('theme/img/e-commerce/5.png') }}" alt=""></div>
                  </div>
                </div>
              </div>
            </div>
          

                {{-- Top deals carousel --}}
                @if ($featured->isNotEmpty())
                    <div class="d-flex flex-between-center mb-3">
                        <div class="d-flex align-items-center"><span class="fas fa-bolt text-warning fs-6"></span>
                            <h3 class="mx-2 mb-0">Top deals today</h3><span class="fas fa-bolt text-warning fs-6"></span>
                        </div>
                        <a class="btn btn-link btn-lg p-0 d-none d-md-block" href="{{ route('products.index') }}">Explore more<span class="fas fa-chevron-right fs-9 ms-1"></span></a>
                    </div>
                    <div class="swiper-theme-container products-slider mb-8">
                        <div class="swiper theme-slider" data-swiper='{{ $dealsSwiper }}'>
                            <div class="swiper-wrapper">
                                @foreach ($featured as $product)
                                    <div class="swiper-slide">
                                        <x-product-card :product="$product" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Promo banner (premium on mobile too) --}}
                <!-- <div class="gb-promo p-4 p-md-5 mb-8">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-8">
                            <span class="gb-promo-badge mb-3"><span class="fas fa-truck-fast"></span>Free delivery</span>
                            <h2 class="text-white fw-bolder mb-1">Free delivery on wholesale orders</h2>
                            <p class="text-white-50 mb-0">Get approved for wholesale pricing and we'll cover delivery on qualifying orders.</p>
                        </div>
                        <div class="col-12 col-md-4 text-md-end">
                            <a class="btn btn-lg btn-light rounded-pill fw-bold" href="{{ route('register') }}">Become a member</a>
                        </div>
                    </div>
                </div> -->

                {{-- New arrivals --}}
                <div class="d-flex flex-between-center mb-3">
                    <h3 class="mb-0">New arrivals</h3>
                    <a class="btn btn-link btn-lg p-0 d-none d-md-block" href="{{ route('products.index') }}">Explore more<span class="fas fa-chevron-right fs-9 ms-1"></span></a>
                </div>
                <div class="row gx-3 gy-5">
                    @forelse ($latest as $product)
                        <div class="col-6 col-md-4 col-lg-3 col-xxl-2">
                            <x-product-card :product="$product" />
                        </div>
                    @empty
                        <p class="text-body-tertiary">No products yet.</p>
                    @endforelse
                </div>
                   <div class="row flex-center mb-15 mt-11 gy-6">
              <div class="col-auto"><img class="d-dark-none" src="{{ asset('theme/img/illustrations/light_30.png') }}" alt="" width="305"><img class="d-light-none" src="{{ asset('theme/img/illustrations/dark_30.png') }}" alt="" width="305"></div>
              <div class="col-auto">
                <div class="text-center text-lg-start">
                  <h3 class="text-body-highlight mb-2"><span class="fw-semibold">Want to have the </span>ultimate <br class="d-md-none">customer experience?</h3>
                  <h1 class="display-3 fw-semibold mb-4">Become a <span class="text-primary fw-bolder">member </span>today!</h1><a class="btn btn-lg btn-primary px-7" href="{{ route('register') }}">Sign up<span class="fas fa-chevron-right ms-2 fs-9"></span></a>
                </div>
              </div>
            </div>
            </div>
        </section>
    </div>
@endsection
