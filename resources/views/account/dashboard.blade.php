@extends('layouts.account')

@section('title', 'My Account Dashboard — Quintessential Mart')

@php
    $pageTitle = 'Dashboard';
@endphp

@section('account_content')
    {{-- Alerts --}}
    @if (! $user->hasVerifiedEmail())
        <div class="alert alert-subtle-warning d-flex flex-between-center flex-wrap gap-2 shadow-sm border-0 mb-4" role="alert">
            <span class="fw-semibold"><span class="fas fa-envelope text-warning fs-5 me-2"></span>Please verify your email address to secure your account.</span>
            <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning">Resend verification email</button>
            </form>
        </div>
    @endif

    {{-- Welcome & Summary --}}
    <div class="row g-3 mb-4">
        {{-- Active Orders --}}
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-actions-trigger transition-base">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="fas fa-box text-primary fs-5 me-2"></span>
                        <h6 class="mb-0 text-body-tertiary">Active Orders</h6>
                    </div>
                    <h3 class="mb-2 text-body-emphasis">{{ $user->orders()->whereNotIn('status', ['delivered', 'cancelled', 'completed', 'failed'])->count() }}</h3>
                    <a href="{{ route('account.orders') }}" class="fs-9 fw-semibold stretched-link text-decoration-none">View all orders <span class="fas fa-angle-right ms-1"></span></a>
                </div>
            </div>
        </div>

        {{-- Store Credit --}}
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm hover-actions-trigger transition-base">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="fas fa-wallet text-success fs-5 me-2"></span>
                        <h6 class="mb-0 text-body-tertiary">Store Credit</h6>
                    </div>
                    <h3 class="mb-2 text-body-emphasis">{{ money($balance) }}</h3>
                    <a href="{{ route('account.wallet') }}" class="fs-9 fw-semibold stretched-link text-decoration-none">View history <span class="fas fa-angle-right ms-1"></span></a>
                </div>
            </div>
        </div>

        {{-- Wholesale Status --}}
        <div class="col-12 col-md-4">
            <div class="card h-100 border-0 shadow-sm transition-base">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="fas fa-tag text-info fs-5 me-2"></span>
                        <h6 class="mb-0 text-body-tertiary">Account Type</h6>
                    </div>
                    @if ($user->isWholesale())
                        <h4 class="mb-2 text-success">Wholesale</h4>
                        <p class="fs-9 text-body-secondary mb-0">You get wholesale pricing.</p>
                    @elseif ($user->hasPendingWholesaleApplication())
                        <h4 class="mb-2 text-warning">Pending Review</h4>
                        <p class="fs-9 text-body-secondary mb-0">Your application is pending.</p>
                    @else
                        <h4 class="mb-2 text-body-emphasis">Retail</h4>
                        <a href="{{ route('account.wholesale') }}" class="fs-9 fw-semibold text-decoration-none">Apply for wholesale <span class="fas fa-angle-right ms-1"></span></a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <!-- <h5 class="mb-3 text-body-emphasis">Quick Actions</h5>
    <div class="row g-3 mb-5">
        <div class="col-6 col-md-3">
            <a href="{{ route('products.index') }}" class="btn btn-outline-primary w-100 py-3 rounded-3 d-flex flex-column align-items-center justify-content-center gap-2">
                <span class="fas fa-shopping-cart fs-6"></span>
                <span class="fs-9 fw-semibold">Shop Now</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('account.addresses.index') }}" class="btn btn-outline-secondary w-100 py-3 rounded-3 d-flex flex-column align-items-center justify-content-center gap-2 border-translucent">
                <span class="fas fa-map-marker-alt fs-6"></span>
                <span class="fs-9 fw-semibold text-body-emphasis">Addresses</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('account.settings') }}" class="btn btn-outline-secondary w-100 py-3 rounded-3 d-flex flex-column align-items-center justify-content-center gap-2 border-translucent">
                <span class="fas fa-cog fs-6"></span>
                <span class="fs-9 fw-semibold text-body-emphasis">Settings</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('account.returns.index') }}" class="btn btn-outline-secondary w-100 py-3 rounded-3 d-flex flex-column align-items-center justify-content-center gap-2 border-translucent">
                <span class="fas fa-rotate-left fs-6"></span>
                <span class="fs-9 fw-semibold text-body-emphasis">Returns</span>
            </a>
        </div>
    </div> -->

    {{-- Recent Orders --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 text-body-emphasis">Recent Orders</h5>
        <a href="{{ route('account.orders') }}" class="btn btn-link p-0 fs-9 fw-semibold text-decoration-none">View All <span class="fas fa-angle-right ms-1"></span></a>
    </div>
    @include('account._orders-table', ['orders' => $recentOrders])

@endsection
