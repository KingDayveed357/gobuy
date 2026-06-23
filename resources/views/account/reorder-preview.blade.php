@extends('layouts.storefront')

@section('title', 'Reorder — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account.orders') }}">Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Reorder</li>
                </ol>
            </nav>

            <h2 class="mb-1">Reorder from {{ $order->order_number }}</h2>
            <p class="text-body-tertiary mb-4">
                @if ($preview['has_changes'])
                    Some things have changed since your original order — review below, then add the available items to your cart.
                @else
                    Everything's still available at the same price. Add it all back in one click.
                @endif
            </p>

            <div class="card mb-4"><div class="card-body p-0">
                <table class="table mb-0 fs-9 align-middle">
                    <thead><tr>
                        <th>Item</th><th class="text-center">Qty</th><th class="text-end">Price</th><th>Status</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($preview['lines'] as $line)
                            <tr class="{{ $line['addable'] ? '' : 'opacity-50' }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($line['image'])<img src="{{ $line['image'] }}" alt="" style="width:42px;height:42px;object-fit:cover;" class="rounded border">@endif
                                        <span class="fw-semibold text-body-emphasis">{{ $line['name'] }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($line['status'] === \App\Modules\Order\Services\ReorderService::PARTIAL)
                                        <span class="text-warning fw-semibold">{{ $line['available'] }}</span><span class="text-body-tertiary">/{{ $line['wanted'] }}</span>
                                    @else
                                        {{ $line['wanted'] }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($line['current'])
                                        @if ($line['price_changed'])
                                            <span class="text-body-tertiary text-decoration-line-through d-block">{{ money($line['paid']) }}</span>
                                            <span class="fw-semibold {{ $line['current']->lessThan($line['paid']) ? 'text-success' : 'text-danger' }}">{{ money($line['current']) }}</span>
                                        @else
                                            {{ money($line['current']) }}
                                        @endif
                                    @else
                                        <span class="text-body-tertiary">—</span>
                                    @endif
                                </td>
                                <td>
                                    @switch($line['status'])
                                        @case(\App\Modules\Order\Services\ReorderService::OK)
                                            <span class="badge badge-phoenix badge-phoenix-success">{{ $line['price_changed'] ? 'Price changed' : 'Available' }}</span>
                                            @break
                                        @case(\App\Modules\Order\Services\ReorderService::PARTIAL)
                                            <span class="badge badge-phoenix badge-phoenix-warning">Only {{ $line['available'] }} left</span>
                                            @break
                                        @case(\App\Modules\Order\Services\ReorderService::OUT_OF_STOCK)
                                            <span class="badge badge-phoenix badge-phoenix-secondary">Out of stock</span>
                                            @break
                                        @default
                                            <span class="badge badge-phoenix badge-phoenix-secondary">No longer sold</span>
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div></div>

            <div class="d-flex flex-wrap gap-2">
                <form action="{{ route('account.orders.reorder', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary"><span class="fas fa-cart-plus me-2"></span>Add {{ $preview['addable'] }} available item(s) to cart</button>
                </form>
                <a href="{{ route('account.orders') }}" class="btn btn-phoenix-secondary">Back to orders</a>
            </div>
        </div>
    </section>
@endsection
