@props(['title', 'subtitle', 'products', 'class' => 'py-0 mb-9 mt-5', 'viewUrl' => null])

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
                @if ($viewUrl)
                    <a class="btn btn-link btn-sm p-0" href="{{ $viewUrl }}">View all<span class="fas fa-chevron-right fs-10 ms-1"></span></a>
                @endif
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
                {{-- Skeleton loader — visible until Swiper init, then hidden by CSS --}}
                <div class="gb-swiper-skeleton" aria-hidden="true">
                    @for ($i = 0; $i < 6; $i++)
                        <div class="gb-skeleton-card">
                            <div class="gb-skeleton gb-skeleton-img"></div>
                            <div class="gb-skeleton gb-skeleton-line gb-skeleton-line--long"></div>
                            <div class="gb-skeleton gb-skeleton-line gb-skeleton-line--medium"></div>
                            <div class="gb-skeleton gb-skeleton-line gb-skeleton-line--short mt-2"></div>
                            <div class="gb-skeleton gb-skeleton-btn"></div>
                        </div>
                    @endfor
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
