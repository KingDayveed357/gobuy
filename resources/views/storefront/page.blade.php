@extends('layouts.storefront')

@php
    // Works for both the live route (passes $campaign/$ogImage) and the signed
    // preview route (which doesn't) — resolve defensively.
    $campaign = $campaign ?? $page->campaign;
    $ogImage = $ogImage ?? null;
    $ogTitle = $page->meta_title ?: $page->title;
    $ogDesc = $page->meta_description ?: 'Shop '.$page->title.' at Quintessential Mart.';
    $pageUrl = $page->url();
@endphp

@section('title', $ogTitle.' — Quintessential Mart')

@push('meta')
    <meta name="description" content="{{ $ogDesc }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDesc }}">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $page->title, 'item' => $pageUrl],
        ],
    ], JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    @if ($preview ?? false)
        <div class="gb-preview-ribbon text-center fw-semibold py-2 px-3">
            <span class="fas fa-eye me-2"></span>Preview mode — showing draft &amp; scheduled content. This is not the live page.
        </div>
    @endif
    <div class="ecommerce-homepage pt-4 mb-9">
        <section class="py-0 px-xl-3">
            <div class="container px-xl-0 px-xxl-3">

                <nav aria-label="Breadcrumb" class="mb-3">
                    <ol class="breadcrumb mb-0 fs-9">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
                    </ol>
                </nav>

                {{-- Campaign hero opener: a branded band (badge + title + lead) using
                     the campaign's accent, instead of a bare centred heading. --}}
                <header class="gb-page-hero text-center mb-6 {{ $campaign?->accent_color ? 'gb-page-hero--branded' : '' }}"
                        @if ($campaign?->accent_color) style="--gb-page-accent: {{ $campaign->accent_color }};" @endif>
                    @if ($campaign?->badge_text)
                        <span class="gb-page-hero__badge">{{ $campaign->badge_text }}</span>
                    @endif
                    <h1 class="fw-bolder mb-2">{{ $page->title }}</h1>
                    @if ($page->meta_description)
                        <p class="text-body-secondary mb-0 mx-auto gb-page-hero__lead">{{ $page->meta_description }}</p>
                    @endif
                </header>

                @forelse ($sections as $block)
                    <x-merch-section :block="$block" :track="! ($preview ?? false)" />
                @empty
                    <p class="text-center text-body-tertiary py-6">This page has no content yet.</p>
                @endforelse

            </div>
        </section>
    </div>
@endsection
