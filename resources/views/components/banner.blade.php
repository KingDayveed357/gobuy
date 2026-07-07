@props(['banner', 'priority' => false])

@php
    $img = $banner->imageUrl();
    $mobileImg = $banner->mobileImageUrl();
    $dark = $banner->text_theme === 'dark';
    $textClass = $dark ? 'text-dark' : 'text-white';
    $subClass = $dark ? 'text-body-secondary' : 'text-white-50';
    $overlay = max(0, min(100, (int) $banner->overlay_opacity)) / 100;

    // Height + typography come from the design-token scale (gobuy.css :root) so
    // banners share one fluid type system with the rest of the storefront.
    $height = in_array($banner->height, ['sm', 'md', 'lg'], true) ? $banner->height : 'md';
    $titleSize = in_array($banner->title_size, ['sm', 'md', 'lg'], true) ? $banner->title_size : 'md';

    // Content placement + the matching legibility-scrim direction.
    [$align, $scrimDir] = match ($banner->content_position ?? 'start') {
        'center' => ['align-items-center text-center', 'to top'],
        'end' => ['align-items-end text-end', '270deg'],
        default => ['align-items-start', '90deg'],
    };
    $maxW = ($banner->content_position ?? 'start') === 'center' ? '90%' : '62%';

    $scrim = $img
        ? "linear-gradient({$scrimDir}, rgba(0,0,0,{$overlay}) 0%, rgba(0,0,0," . ($overlay * 0.55) . ") 45%, rgba(0,0,0,0) 100%)"
        : null;

    $btnStyle = match ($banner->cta_variant) {
        'primary' => 'btn-primary',
        'dark' => 'btn-dark',
        'outline' => 'btn-outline-light',
        default => 'btn-light',
    };
    $btnSize = match ($banner->cta_size ?? 'md') { 'sm' => 'btn-sm', 'lg' => 'btn-lg', default => '' };
    $btnRadius = match ($banner->cta_radius ?? 'pill') {
        'rounded' => 'rounded-3',
        'square' => 'rounded-0',
        default => 'rounded-pill',
    };

    $hasCountdown = $banner->countdown_to && $banner->countdown_to->isFuture();
    $bannerUrl = $banner->destinationUrl();
@endphp

<div {{ $attributes->merge(['class' => "gb-banner gb-banner--{$height} rounded-3 overflow-hidden position-relative d-flex {$align}"]) }}
     @if (! $img) style="background: {{ $banner->gradient() }};" @endif>

    {{-- Art-directed image layer: mobile crop on small screens, desktop otherwise. --}}
    @if ($img)
        <picture class="gb-banner__img">
            @if ($mobileImg && $mobileImg !== $img)
                <source media="(max-width: 575.98px)" srcset="{{ $mobileImg }}">
            @endif
            {{-- Above-fold banners (first section, first banner) use eager loading
                 + fetchpriority=high to maximise LCP score. All others lazy-load. --}}
            <img src="{{ $img }}"
                 alt="{{ $banner->title }}"
                 @if ($priority) loading="eager" fetchpriority="high" @else loading="lazy" @endif
                 style="object-position: {{ $banner->focal_point ?: 'center' }}">
        </picture>
        <span class="gb-banner__scrim" style="background: {{ $scrim }};"></span>
    @endif

    @if ($banner->ribbon)
        <span class="gb-banner__ribbon">{{ \Illuminate\Support\Str::limit($banner->ribbon, 18) }}</span>
    @endif

    <div class="gb-banner__content p-4 p-md-5" style="max-width: {{ $maxW }};">
        @if ($banner->subtitle)
            <span class="badge {{ $dark ? 'text-bg-light' : 'text-bg-dark' }} bg-opacity-50 mb-2">{{ \Illuminate\Support\Str::limit($banner->subtitle, 48) }}</span>
        @endif

        <h2 class="gb-banner__title--{{ $titleSize }} fw-bolder mb-2 {{ $textClass }}">{{ $banner->title }}</h2>

        @if ($hasCountdown)
            {{-- role="timer" (implicit aria-live=off) — a ticking region must never
                 announce every second; the label carries the human-readable end. --}}
            <div class="gb-countdown {{ $subClass }} mb-2" role="timer"
                 data-countdown="{{ $banner->countdown_to->toIso8601String() }}"
                 aria-label="Offer ends {{ $banner->countdown_to->format('M j, g:i A') }}"></div>
        @endif

        @if ($bannerUrl)
            <a class="btn {{ $btnStyle }} {{ $btnSize }} {{ $btnRadius }} fw-semibold" href="{{ $bannerUrl }}">{{ $banner->cta_label ?: 'Shop now' }}</a>
        @endif
    </div>
</div>
