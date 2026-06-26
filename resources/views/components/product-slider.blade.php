@props(['title', 'subtitle', 'products', 'class' => 'py-0 mb-9 mt-5'])

@php
    $swiperConfig = '{"slidesPerView":1.18,"spaceBetween":10,"watchOverflow":true,"breakpoints":{"576":{"slidesPerView":2.1,"spaceBetween":10},"768":{"slidesPerView":3.1,"spaceBetween":12},"992":{"slidesPerView":4.1,"spaceBetween":12},"1200":{"slidesPerView":5.1,"spaceBetween":12},"1540":{"slidesPerView":6.1,"spaceBetween":12}}}';
@endphp

@if ($products->isNotEmpty())
    <section class="{{ $class }}">
        <div class="container-small">
            <div class="d-flex flex-between-center mb-4">
                <div>
                    <h3 class="mb-1">{{ $title }}</h3>
                    @if ($subtitle)
                        <p class="text-body-tertiary mb-0 fs-9">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            <!-- Fix for carousel overlapping issues: removed position-relative from the wrapper to decouple stacking contexts -->
            <div class="swiper-theme-container products-slider">
                <div class="swiper theme-slider" data-swiper='{{ $swiperConfig }}'>
                    <div class="swiper-wrapper">
                        @foreach ($products as $item)
                            <div class="swiper-slide">
                                <x-product-card :product="$item" />
                            </div>
                        @endforeach
                    </div>
                </div>
                <!-- Moved swiper-nav AFTER the swiper container so it naturally stacks above the slides -->
                <div class="swiper-nav" style="pointer-events: none;">
                    <div class="swiper-button-next" style="pointer-events: auto;"><span class="fas fa-chevron-right nav-icon"></span></div>
                    <div class="swiper-button-prev" style="pointer-events: auto;"><span class="fas fa-chevron-left nav-icon"></span></div>
                </div>
            </div>
        </div>
    </section>
@endif
