@extends('layouts.storefront')

@section('title', 'Quintessential Mart — Retail & Wholesale, delivered fast')

@section('content')
    @if ($preview ?? false)
        <div class="gb-preview-ribbon text-center fw-semibold py-2 px-3">
            <span class="fas fa-eye me-2"></span>Preview mode — showing draft &amp; scheduled content. This is not the live homepage.
        </div>
    @endif
    <div class="ecommerce-homepage pt-5 mb-9">
        <section class="py-0 px-xl-3">
            <div class="container px-xl-0 px-xxl-3">

                {{-- Dynamic merchandising sections — composed & scheduled by the
                     marketing team via the admin Merchandising builder. --}}
                @foreach ($sections as $block)
                    <x-merch-section :block="$block" :track="! ($preview ?? false)" />
                @endforeach

                {{-- Evergreen membership CTA --}}
                <div class="row flex-center mb-15 mt-11 gy-6">
                    <div class="col-auto"><img class="d-dark-none" src="{{ asset('theme/img/illustrations/light_30.png') }}" alt="" width="305"><img class="d-light-none" src="{{ asset('theme/img/illustrations/dark_30.png') }}" alt="" width="305"></div>
                    <div class="col-auto">
                        <div class="text-center text-lg-start">
                            <h3 class="text-body-highlight mb-2"><span class="fw-semibold">Want to have the </span>ultimate <br class="d-md-none">customer experience?</h3>
                            {{-- h2 not h1 — this is a marketing CTA, not the page title --}}
                            <h2 class="display-3 fw-semibold mb-4">Become a <span class="text-primary fw-bolder">member </span>today!</h2><a class="btn btn-lg btn-primary px-7" href="{{ route('register') }}">Sign up<span class="fas fa-chevron-right ms-2 fs-9"></span></a>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>
@endsection
