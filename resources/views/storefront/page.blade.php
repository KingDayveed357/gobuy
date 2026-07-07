@extends('layouts.storefront')

@section('title', ($page->meta_title ?: $page->title).' — gobuy')

@push('meta')
    @if ($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif
@endpush

@section('content')
    @if ($preview ?? false)
        <div class="gb-preview-ribbon text-center fw-semibold py-2 px-3">
            <span class="fas fa-eye me-2"></span>Preview mode — showing draft &amp; scheduled content. This is not the live page.
        </div>
    @endif
    <div class="ecommerce-homepage pt-5 mb-9">
        <section class="py-0 px-xl-3">
            <div class="container px-xl-0 px-xxl-3">

                <div class="text-center mb-6">
                    <h1 class="fw-bolder mb-0">{{ $page->title }}</h1>
                </div>

                @forelse ($sections as $block)
                    <x-merch-section :block="$block" :track="! ($preview ?? false)" />
                @empty
                    <p class="text-center text-body-tertiary py-6">This page has no content yet.</p>
                @endforelse

            </div>
        </section>
    </div>
@endsection
