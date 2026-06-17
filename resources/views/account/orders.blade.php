@extends('layouts.storefront')

@section('title', 'Order history — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Orders</li>
                </ol>
            </nav>
            <h2 class="mb-4">Order history</h2>

            @include('account._orders-table', ['orders' => $orders])

            <div class="mt-4">{{ $orders->links() }}</div>
        </div>
    </section>
@endsection
