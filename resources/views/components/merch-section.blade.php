@props(['block', 'track' => false])

@php
    use App\Modules\Marketing\Enums\SectionType;

    $section = $block->section;
    $items = $block->items;
    $type = $section->type;
    $sectionUrl = $section->destinationUrl();

    $railSwiper = '{"slidesPerView":2.1,"spaceBetween":10,"breakpoints":{"576":{"slidesPerView":2.3,"spaceBetween":12},"768":{"slidesPerView":3.2,"spaceBetween":14},"992":{"slidesPerView":4.2,"spaceBetween":16},"1200":{"slidesPerView":5.2,"spaceBetween":16}}}';

    // Compact category-icon resolver (kept local so the section is self-contained).
    $iconFor = function ($category) {
        $name = \Illuminate\Support\Str::lower($category->name);
        foreach ([
            'phone' => 'fa-mobile-screen-button', 'laptop' => 'fa-laptop', 'computer' => 'fa-desktop',
            'electronic' => 'fa-plug', 'fashion' => 'fa-shirt', 'cloth' => 'fa-shirt', 'shoe' => 'fa-shoe-prints',
            'home' => 'fa-house', 'kitchen' => 'fa-utensils', 'grocer' => 'fa-basket-shopping', 'food' => 'fa-utensils',
            'beauty' => 'fa-spa', 'health' => 'fa-heart-pulse', 'sport' => 'fa-futbol', 'toy' => 'fa-puzzle-piece',
            'book' => 'fa-book', 'car' => 'fa-car', 'baby' => 'fa-baby', 'furniture' => 'fa-couch', 'game' => 'fa-gamepad',
        ] as $kw => $icon) {
            if (str_contains($name, $kw)) { return $icon; }
        }
        return 'fa-tags';
    };
@endphp

{{-- Vertical rhythm comes from the --gb-section-gap design token. --}}
<section class="gb-merch-section gb-reveal" @if ($track) data-track-section="{{ $section->id }}" @endif>
    {{-- Uniform section header (the flash-sale + editorial types render their own) --}}
    @if (($section->title || $sectionUrl) && $type !== SectionType::CountdownDeal && ! $type->isEditorial())
        <div class="d-flex flex-between-center mb-3">
            <div class="d-flex align-items-center gap-2">
                @if ($type === SectionType::ProductRail)
                    <span class="fas fa-bolt text-warning fs-6"></span>
                @endif
                <div>
                    @if ($section->title)<h3 class="mb-0">{{ $section->title }}</h3>@endif
                    @if ($section->subtitle)<p class="text-body-tertiary fs-9 mb-0">{{ $section->subtitle }}</p>@endif
                </div>
            </div>
            @if ($sectionUrl)
                {{-- CTA visible on all viewports — mobile users need discoverability too --}}
                <a class="btn btn-link btn-sm p-0" href="{{ $sectionUrl }}">{{ $section->cta_label ?: 'View all' }}<span class="fas fa-chevron-right fs-10 ms-1"></span></a>
            @endif
        </div>
    @endif

    @switch($type)
        @case(SectionType::BannerRow)
            <div class="row g-3">
                @foreach ($items as $banner)
                    @php
                        $col = match ($banner->layout) {
                            'grid' => 'col-12 col-sm-6 col-lg-4',
                            'split' => 'col-12 col-md-6 col-lg-6',
                            default => 'col-12'
                        };
                    @endphp
                    {{-- First banner in the first-rendered section gets eager loading for LCP --}}
                    <div class="{{ $col }}"><x-banner :banner="$banner" :priority="$track && $loop->first" class="h-100" /></div>
                @endforeach
            </div>
            @break

        @case(SectionType::ProductRail)
            <div class="swiper-theme-container products-slider">
                <div class="swiper theme-slider" data-swiper='{{ $railSwiper }}'>
                    <div class="swiper-wrapper">
                        @foreach ($items as $product)
                            <div class="swiper-slide"><x-product-card :product="$product" /></div>
                        @endforeach
                    </div>
                </div>
                {{-- Skeleton loader — visible until Swiper init, then hidden by CSS adjacent sibling selector --}}
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
                {{-- Navigation arrows — DOM-rendered to avoid layout shift on init --}}
                <div class="swiper-nav" style="pointer-events:none;">
                    <div class="swiper-button-next" aria-label="Next" style="pointer-events:auto;"><span class="fas fa-chevron-right nav-icon"></span></div>
                    <div class="swiper-button-prev" aria-label="Previous" style="pointer-events:auto;"><span class="fas fa-chevron-left nav-icon"></span></div>
                </div>
            </div>
            @break

        @case(SectionType::ProductGrid)
            <div class="row gx-3 gy-5">
                @foreach ($items as $product)
                    <div class="col-6 col-md-4 col-lg-3 col-xxl-2"><x-product-card :product="$product" /></div>
                @endforeach
            </div>
            @break

        @case(SectionType::CountdownDeal)
            <div class="gb-flash-bar d-flex flex-wrap align-items-center gap-2 gap-md-3 px-3 py-2 mb-3 rounded-3">
                <span class="fas fa-bolt"></span>
                <h3 class="mb-0 fs-5 text-white">{{ $section->title ?: 'Flash sale' }}</h3>
                @if ($section->ends_at && $section->ends_at->isFuture())
                    <span class="ms-md-auto d-flex align-items-center gap-2">
                        <span class="fs-9 text-white-50 d-none d-sm-inline">Ends in</span>
                        <span class="gb-countdown gb-flash-countdown badge text-bg-light fs-9" role="timer"
                              data-countdown="{{ $section->ends_at->toIso8601String() }}"
                              aria-label="Sale ends {{ $section->ends_at->format('M j, g:i A') }}">
                            <span class="fas fa-clock me-1"></span>{{ $section->ends_at->format('H:i:s') }}
                        </span>
                    </span>
                @endif
                @if ($sectionUrl)
                    <a class="btn btn-sm btn-light rounded-pill {{ $section->ends_at ? '' : 'ms-md-auto' }}" href="{{ $sectionUrl }}">{{ $section->cta_label ?: 'See all' }}</a>
                @endif
            </div>
            <div class="swiper-theme-container products-slider">
                <div class="swiper theme-slider" data-swiper='{{ $railSwiper }}'>
                    <div class="swiper-wrapper">
                        @foreach ($items as $product)
                            <div class="swiper-slide"><x-product-card :product="$product" :urgency="true" /></div>
                        @endforeach
                    </div>
                </div>
                {{-- Skeleton loader — visible until Swiper init, then hidden by CSS adjacent sibling selector --}}
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
                {{-- Navigation arrows — DOM-rendered to avoid layout shift on init --}}
                <div class="swiper-nav" style="pointer-events:none;">
                    <div class="swiper-button-next" aria-label="Next" style="pointer-events:auto;"><span class="fas fa-chevron-right nav-icon"></span></div>
                    <div class="swiper-button-prev" aria-label="Previous" style="pointer-events:auto;"><span class="fas fa-chevron-left nav-icon"></span></div>
                </div>
            </div>
            @break

        @case(SectionType::CategoryGrid)
            <div class="gb-category-strip-wrap" style="overflow-x: auto; scrollbar-width: none;">
                <div class="gb-category-strip" style="display: flex; flex-wrap: nowrap; min-width: min-content; gap: 1rem;">
                @foreach ($items as $category)
                    <a class="gb-category-item" href="{{ route('products.index', ['category' => $category->slug]) }}">
                        <div class="gb-category-tile {{ $category->representative_image ? 'gb-category-tile--image' : '' }}">
                            @if ($category->representative_image)
                                <img src="{{ $category->representative_image }}" alt="{{ $category->name }}" loading="lazy">
                            @else
                                <span class="fas {{ $iconFor($category) }}"></span>
                            @endif
                        </div>
                        <p class="gb-category-label text-nowrap">{{ $category->name }}</p>
                    </a>
                @endforeach
                </div>
            </div>
            @break

        @case(SectionType::BrandRail)
            <div class="gb-brand-rail">
                @foreach ($items as $brand)
                    <a class="gb-brand-pill" href="{{ route('products.index', ['brand' => $brand->slug]) }}">
                        <span class="fas fa-tag fs-11" aria-hidden="true"></span>
                        {{ $brand->name }}
                    </a>
                @endforeach
            </div>
            @break

        @case(SectionType::RichText)
            @php
                $align = $section->setting('align', 'center');
            @endphp
            <div class="gb-editorial {{ $section->setting('theme') === 'accent' ? 'gb-editorial--accent' : '' }} text-{{ $align }} p-4 p-md-6 rounded-4">
                <div class="{{ $align === 'center' ? 'mx-auto' : '' }}" style="max-width: 720px;">
                    @if ($section->setting('eyebrow'))<p class="gb-editorial__eyebrow text-uppercase fw-bold fs-10 mb-2">{{ $section->setting('eyebrow') }}</p>@endif
                    @if ($section->title)<h2 class="gb-editorial__title fw-bolder mb-3">{{ $section->title }}</h2>@endif
                    @if ($section->setting('body'))<div class="gb-editorial__body fs-8 text-body-secondary">{!! nl2br(e($section->setting('body'))) !!}</div>@endif
                    @if ($sectionUrl)<a class="btn btn-primary mt-4 px-5" href="{{ $sectionUrl }}">{{ $section->cta_label ?: 'Learn more' }}</a>@endif
                </div>
            </div>
            @break

        @case(SectionType::EditorialMedia)
            {{-- Normalise image URL: strip malformed single-slash protocol written by
                 the demo seeder (http:/host vs http://host). Also handle relative paths. --}}
            @php
                $mediaRight = $section->setting('align') === 'right';
                $editorialImageUrl = $section->setting('image_url');

                if ($editorialImageUrl) {
                    // Fix seeder-generated URLs like 'http:/127.0.0.1:8000/...' → relative
                    if (preg_match('#^https?:/(?!/|$)#', $editorialImageUrl)) {
                        // Malformed absolute URL from old seeder — extract path portion
                        $editorialImageUrl = preg_replace('#^https?:/[^/]+#', '', $editorialImageUrl);
                    }
                    // If it's still an absolute URL starting with http(s):// it's fine
                    // If it's a root-relative path (/storage/...) it's fine
                    // If it's an asset path (theme/...) prefix with /
                    if ($editorialImageUrl && !str_starts_with($editorialImageUrl, 'http') && !str_starts_with($editorialImageUrl, '/')) {
                        $editorialImageUrl = '/' . $editorialImageUrl;
                    }
                }
            @endphp
            <div class="gb-editorial-media row g-0 align-items-stretch rounded-4 overflow-hidden {{ $mediaRight ? 'flex-md-row-reverse' : '' }} bg-body-highlight">
                <div class="col-12 col-md-6 gb-editorial-media__figure {{ $editorialImageUrl ? '' : 'gb-editorial-media__figure--empty' }}">
                    @if ($editorialImageUrl)
                        {{-- Proper <img> tag: visible to screen readers, search engines,
                             lazy-loaded, no JS required, and shows in DevTools. --}}
                        <img src="{{ $editorialImageUrl }}"
                             alt="{{ $section->title ?: '' }}"
                             loading="lazy">
                    @else
                        <span class="fas fa-image" aria-hidden="true"></span>
                    @endif
                </div>
                <div class="col-12 col-md-6 gb-editorial-media__copy d-flex flex-column justify-content-center p-5 p-lg-7 p-xl-8">
                    @if ($section->setting('eyebrow'))<p class="gb-editorial__eyebrow text-uppercase fw-bold ls-2 text-primary fs-9 mb-3">{{ $section->setting('eyebrow') }}</p>@endif
                    @if ($section->title)<h2 class="display-5 fw-bolder mb-4 text-body-emphasis">{{ $section->title }}</h2>@endif
                    @if ($section->setting('body'))<div class="fs-8 text-body-secondary mb-5 leading-relaxed">{!! nl2br(e($section->setting('body'))) !!}</div>@endif
                    @if ($sectionUrl)<div><a class="btn btn-primary rounded-pill px-6" href="{{ $sectionUrl }}">{{ $section->cta_label ?: 'Discover' }}</a></div>@endif
                </div>
            </div>
            @break
    @endswitch
</section>
