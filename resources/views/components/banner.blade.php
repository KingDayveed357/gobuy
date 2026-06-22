@props(['banner'])

@php
    $img = $banner->imageUrl();
    $mobileImg = $banner->mobileImageUrl();
    $textClass = $banner->text_theme === 'dark' ? 'text-dark' : 'text-white';
    $subClass = $banner->text_theme === 'dark' ? 'text-body-secondary' : 'text-white-50';
    $overlay = max(0, min(100, (int) $banner->overlay_opacity)) / 100;
    // Image background with a dark scrim for legibility; gradient preset otherwise.
    $bg = $img
        ? "linear-gradient(90deg, rgba(0,0,0,{$overlay}) 0%, rgba(0,0,0," . ($overlay * 0.5) . ") 60%, rgba(0,0,0,0) 100%), center/cover no-repeat url('{$img}')"
        : $banner->gradient();
    $btnClass = match ($banner->cta_variant) {
        'primary' => 'btn-primary',
        'dark' => 'btn-dark',
        'outline' => 'btn-outline-light',
        default => 'btn-light',
    };
    $minH = $banner->layout === 'grid' ? '180px' : '240px';
@endphp

<div {{ $attributes->merge(['class' => 'gb-banner rounded-3 overflow-hidden position-relative d-flex align-items-center']) }}
     style="min-height: {{ $minH }}; background: {{ $bg }}; background-position: {{ $banner->focal_point }};">
    <div class="p-5 p-md-6 position-relative" style="max-width: {{ $banner->layout === 'split' ? '58%' : '100%' }};">
        @if ($banner->subtitle && $banner->layout !== 'grid')
            <span class="badge {{ $banner->text_theme === 'dark' ? 'text-bg-light' : 'text-bg-dark' }} bg-opacity-50 mb-2">{{ \Illuminate\Support\Str::limit($banner->subtitle, 40) }}</span>
        @endif
        <h2 class="fw-bolder mb-1 {{ $textClass }} {{ $banner->layout === 'grid' ? 'fs-4' : 'fs-2' }}">{{ $banner->title }}</h2>
        @if ($banner->subtitle && $banner->layout === 'grid')
            <p class="{{ $subClass }} fs-9 mb-3">{{ $banner->subtitle }}</p>
        @elseif ($banner->subtitle)
            <p class="{{ $subClass }} fs-7 mb-3">{{ $banner->subtitle }}</p>
        @endif
        @if ($banner->link_url)
            <a class="btn {{ $btnClass }} rounded-pill {{ $banner->layout === 'grid' ? 'btn-sm' : '' }}" href="{{ $banner->link_url }}">{{ $banner->cta_label ?: 'Shop now' }}</a>
        @endif
    </div>
</div>
