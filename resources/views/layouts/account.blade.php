@extends('layouts.storefront')

@section('title', $title ?? 'My Account — Quintessential Mart')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container">
            <div class="row g-4">
                {{-- Desktop Sidebar --}}
                <div class="col-12 col-lg-3 d-none d-lg-block">
                    @include('account.partials._sidebar')
                </div>

                {{-- Main Content --}}
                <div class="col-12 col-lg-9">
                    {{-- Mobile Account Menu Header --}}
                    <div class="d-lg-none mb-4 d-flex justify-content-between align-items-center bg-body-highlight p-3 rounded-3 shadow-sm border border-translucent">
                        <h5 class="mb-0 text-body-emphasis">{{ $pageTitle ?? 'My Account' }}</h5>
                        <button class="btn btn-phoenix-secondary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAccount" aria-controls="offcanvasAccount">
                            <span class="fas fa-bars me-1"></span> Menu
                        </button>
                    </div>

                    {{-- Content Area --}}
                    <div class="account-content-wrapper">
                        @yield('account_content')
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Mobile Offcanvas --}}
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasAccount" aria-labelledby="offcanvasAccountLabel" style="max-width: 280px;">
        <div class="offcanvas-header d-flex align-items-center justify-content-between border-bottom border-translucent px-4 py-3">
            <h5 class="offcanvas-title mb-0 text-body-emphasis" id="offcanvasAccountLabel">My Account</h5>
            <button class="btn btn-phoenix-secondary btn-icon rounded-circle d-flex align-items-center justify-content-center" type="button" data-bs-dismiss="offcanvas" aria-label="Close" style="width: 32px; height: 32px; padding: 0;">
                <span class="fas fa-times fs-9"></span>
            </button>
        </div>
        <div class="offcanvas-body p-0">
            @include('account.partials._sidebar', ['isMobile' => true])
        </div>
    </div>
@endsection
