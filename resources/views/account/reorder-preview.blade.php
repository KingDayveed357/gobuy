@extends('layouts.account')

@section('title', 'Reorder — Quintessential Mart')

@php
    $pageTitle = 'Reorder';
@endphp

@section('account_content')
    <div class="mb-4">
        <a href="{{ route('account.orders') }}" class="btn btn-link px-0 text-body-tertiary mb-2"><span class="fas fa-arrow-left me-2"></span>Back to Orders</a>
        <h3 class="mb-1 text-body-emphasis">Reorder from {{ $order->order_number }}</h3>
        <p class="text-body-tertiary">
            @if ($preview['has_changes'])
                Some things have changed since your original order — review below, then add the available items to your cart.
            @else
                Everything's still available at the same price. Add it all back in one click.
            @endif
        </p>
    </div>

    {{-- Desktop Table --}}
    <div class="card border-0 shadow-sm mb-4 d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-sm fs-9 mb-0 align-middle">
                <thead class="bg-body-highlight">
                    <tr>
                        <th class="ps-4 border-0 py-3">Item</th>
                        <th class="text-center border-0 py-3">Qty</th>
                        <th class="text-end border-0 py-3">Price</th>
                        <th class="pe-4 border-0 py-3 text-end">Status</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @foreach ($preview['lines'] as $line)
                        <tr class="{{ $line['addable'] ? '' : 'opacity-50' }}">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    @if ($line['image'])
                                        <img src="{{ $line['image'] }}" alt="" style="width:48px;height:48px;object-fit:cover;" class="rounded-3 border border-translucent">
                                    @else
                                        <div class="bg-body-highlight rounded-3 border border-translucent d-flex flex-center" style="width:48px;height:48px;">
                                            <span class="fas fa-image text-body-tertiary"></span>
                                        </div>
                                    @endif
                                    <span class="fw-bold text-body-emphasis">{{ $line['name'] }}</span>
                                </div>
                            </td>
                            <td class="text-center py-3 fw-semibold">
                                @if ($line['status'] === \App\Modules\Order\Services\ReorderService::PARTIAL)
                                    <span class="text-warning">{{ $line['available'] }}</span><span class="text-body-tertiary">/{{ $line['wanted'] }}</span>
                                @else
                                    {{ $line['wanted'] }}
                                @endif
                            </td>
                            <td class="text-end py-3">
                                @if ($line['current'])
                                    @if ($line['price_changed'])
                                        <span class="text-body-tertiary text-decoration-line-through d-block fs-10">{{ money($line['paid']) }}</span>
                                        <span class="fw-bold {{ $line['current']->lessThan($line['paid']) ? 'text-success' : 'text-danger' }}">{{ money($line['current']) }}</span>
                                    @else
                                        <span class="fw-bold text-body-emphasis">{{ money($line['current']) }}</span>
                                    @endif
                                @else
                                    <span class="text-body-tertiary fw-bold">—</span>
                                @endif
                            </td>
                            <td class="pe-4 py-3 text-end">
                                @switch($line['status'])
                                    @case(\App\Modules\Order\Services\ReorderService::OK)
                                        <span class="badge badge-phoenix badge-phoenix-success rounded-pill">{{ $line['price_changed'] ? 'Price changed' : 'Available' }}</span>
                                        @break
                                    @case(\App\Modules\Order\Services\ReorderService::PARTIAL)
                                        <span class="badge badge-phoenix badge-phoenix-warning rounded-pill">Only {{ $line['available'] }} left</span>
                                        @break
                                    @case(\App\Modules\Order\Services\ReorderService::OUT_OF_STOCK)
                                        <span class="badge badge-phoenix badge-phoenix-secondary rounded-pill">Out of stock</span>
                                        @break
                                    @default
                                        <span class="badge badge-phoenix badge-phoenix-secondary rounded-pill">No longer sold</span>
                                @endswitch
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile Cards --}}
    <div class="d-md-none d-flex flex-column gap-3 mb-4">
        @foreach ($preview['lines'] as $line)
            <div class="card border-translucent shadow-none rounded-3 {{ $line['addable'] ? '' : 'opacity-50' }}">
                <div class="card-body p-3">
                    <div class="d-flex gap-3 align-items-center mb-3">
                        @if ($line['image'])
                            <img src="{{ $line['image'] }}" alt="" style="width:48px;height:48px;object-fit:cover;" class="rounded-3 border border-translucent">
                        @else
                            <div class="bg-body-highlight rounded-3 border border-translucent d-flex flex-center" style="width:48px;height:48px;">
                                <span class="fas fa-image text-body-tertiary"></span>
                            </div>
                        @endif
                        <div class="flex-1">
                            <span class="fw-bold text-body-emphasis d-block lh-sm mb-1">{{ $line['name'] }}</span>
                            @switch($line['status'])
                                @case(\App\Modules\Order\Services\ReorderService::OK)
                                    <span class="badge badge-phoenix badge-phoenix-success rounded-pill fs-10">{{ $line['price_changed'] ? 'Price changed' : 'Available' }}</span>
                                    @break
                                @case(\App\Modules\Order\Services\ReorderService::PARTIAL)
                                    <span class="badge badge-phoenix badge-phoenix-warning rounded-pill fs-10">Only {{ $line['available'] }} left</span>
                                    @break
                                @case(\App\Modules\Order\Services\ReorderService::OUT_OF_STOCK)
                                    <span class="badge badge-phoenix badge-phoenix-secondary rounded-pill fs-10">Out of stock</span>
                                    @break
                                @default
                                    <span class="badge badge-phoenix badge-phoenix-secondary rounded-pill fs-10">No longer sold</span>
                            @endswitch
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-end fs-9 bg-body-highlight p-2 rounded-2">
                        <div>
                            <span class="text-body-tertiary d-block fs-10 mb-1">Quantity</span>
                            <span class="fw-semibold text-body-emphasis">
                                @if ($line['status'] === \App\Modules\Order\Services\ReorderService::PARTIAL)
                                    <span class="text-warning">{{ $line['available'] }}</span><span class="text-body-tertiary">/{{ $line['wanted'] }}</span>
                                @else
                                    {{ $line['wanted'] }}
                                @endif
                            </span>
                        </div>
                        <div class="text-end">
                            <span class="text-body-tertiary d-block fs-10 mb-1">Price</span>
                            @if ($line['current'])
                                @if ($line['price_changed'])
                                    <span class="text-body-tertiary text-decoration-line-through me-1 fs-10">{{ money($line['paid']) }}</span>
                                    <span class="fw-bold {{ $line['current']->lessThan($line['paid']) ? 'text-success' : 'text-danger' }}">{{ money($line['current']) }}</span>
                                @else
                                    <span class="fw-bold text-body-emphasis">{{ money($line['current']) }}</span>
                                @endif
                            @else
                                <span class="text-body-tertiary fw-bold">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex flex-wrap gap-2">
        <form action="{{ route('account.orders.reorder', $order) }}" method="POST" class="w-100 w-sm-auto">
            @csrf
            <button type="submit" class="btn btn-primary w-100"><span class="fas fa-cart-plus me-2"></span>Add {{ $preview['addable'] }} available item(s) to cart</button>
        </form>
    </div>
@endsection
