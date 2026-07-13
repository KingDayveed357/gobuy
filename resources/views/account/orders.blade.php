@extends('layouts.account')

@section('title', 'Order history — Quintessential Mart')

@php
    $pageTitle = 'Order History';
@endphp

@section('account_content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h3 class="mb-0 text-body-emphasis">Order History</h3>
        
        {{-- Search & Filters --}}
        <div class="d-flex gap-2 w-100 w-lg-auto">
            <form action="{{ route('account.orders') }}" method="GET" class="d-flex flex-wrap flex-md-nowrap gap-2 w-100 align-items-center">
                <div class="input-group input-group-sm flex-grow-1" style="min-width: 280px;">
                    <span class="input-group-text bg-transparent border-translucent border-end-0">
                        <span class="fas fa-search fs-10 text-body-tertiary"></span>
                    </span>
                    <input type="text" name="search" class="form-control border-translucent border-start-0 @if(request('search')) border-end-0 @endif" placeholder="Search by Order #..." value="{{ request('search') }}">
                    @if(request('search'))
                        <a href="{{ route('account.orders', request()->except('search')) }}" class="input-group-text bg-transparent border-translucent border-start-0 text-body-tertiary hover-text-danger" title="Clear search">
                            <span class="fas fa-times-circle"></span>
                        </a>
                    @endif
                </div>
                <select name="status" class="form-select form-select-sm border-translucent" style="min-width: 130px; max-width: 150px;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Shipped</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                <select name="sort" class="form-select form-select-sm border-translucent" style="min-width: 140px; max-width: 160px;" onchange="this.form.submit()">
                    <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest first</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                    <option value="highest_value" {{ request('sort') === 'highest_value' ? 'selected' : '' }}>Highest Value</option>
                    <option value="lowest_value" {{ request('sort') === 'lowest_value' ? 'selected' : '' }}>Lowest Value</option>
                </select>
                <noscript><button type="submit" class="btn btn-sm btn-primary">Apply</button></noscript>
            </form>
        </div>
    </div>

    @include('account._orders-table', ['orders' => $orders])

    @if ($orders->hasPages())
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
@endsection
