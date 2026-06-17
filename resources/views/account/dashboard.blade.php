@extends('layouts.storefront')

@section('title', 'My account — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <div class="d-flex flex-between-center mb-5">
                <div>
                    <h2 class="mb-1">Hi, {{ $user->name }}</h2>
                    <p class="text-body-tertiary mb-0">{{ $user->email }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-phoenix-secondary" type="submit"><span class="fas fa-sign-out-alt me-2"></span>Sign out</button>
                </form>
            </div>

            <div class="row g-3 mb-5">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="text-body-tertiary mb-2">Account type</h6>
                            @if ($user->isWholesale())
                                <span class="badge badge-phoenix badge-phoenix-success fs-9">Wholesale</span>
                            @elseif ($user->hasPendingWholesaleApplication())
                                <span class="badge badge-phoenix badge-phoenix-warning fs-9">Wholesale — pending review</span>
                            @else
                                <span class="badge badge-phoenix badge-phoenix-info fs-9">Retail</span>
                                <p class="fs-9 mt-2 mb-0"><a href="{{ route('account.wholesale') }}">Apply for wholesale pricing →</a></p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="text-body-tertiary mb-2">Orders</h6>
                            <a href="{{ route('account.orders') }}" class="btn btn-link p-0">View order history →</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="text-body-tertiary mb-2">Keep shopping</h6>
                            <a href="{{ route('products.index') }}" class="btn btn-link p-0">Browse products →</a>
                        </div>
                    </div>
                </div>
            </div>

            <h4 class="mb-3">Recent orders</h4>
            @include('account._orders-table', ['orders' => $recentOrders])
        </div>
    </section>
@endsection
