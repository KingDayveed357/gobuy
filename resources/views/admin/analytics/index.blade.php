@extends('admin.layouts.app')

@section('title', 'Analytics — gobuy admin')
@section('page-title', 'Analytics')

@php($maxRevenue = max(1, $revenueByDay->max('value')))

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <span class="text-body-tertiary fs-9">Revenue (paid)</span>
                <div class="kpi-value mt-2">{{ money($totals['revenue']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <span class="text-body-tertiary fs-9">Paid orders</span>
                <div class="kpi-value mt-2">{{ number_format($totals['paid_orders']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <span class="text-body-tertiary fs-9">Avg order value</span>
                <div class="kpi-value mt-2">{{ money($totals['average_order_value']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <span class="text-body-tertiary fs-9">Refunded</span>
                <div class="kpi-value mt-2">{{ money($totals['refunded']) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <x-admin.card title="Revenue — last 14 days" subtitle="Daily paid revenue at a glance." class="h-100">
                <div class="d-flex align-items-end gap-2" style="height: 220px;">
                    @foreach ($revenueByDay as $day)
                        <div class="flex-1 d-flex flex-column align-items-center justify-content-end h-100" title="{{ $day['label'] }}: {{ money($day['value']) }}">
                            <div class="w-100 rounded-top bg-primary" style="height: {{ max(2, round(($day['value'] / $maxRevenue) * 100)) }}%; min-height: 2px; opacity: {{ $day['value'] > 0 ? 1 : 0.25 }};"></div>
                            <span class="fs-10 text-body-tertiary mt-1" style="white-space: nowrap;">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-admin.card>
        </div>
        <div class="col-12 col-xl-4">
            <x-admin.card title="Orders by status" subtitle="Distribution of the current order pipeline." class="h-100" flush>
                <table class="table admin-table mb-0">
                    <tbody>
                        @foreach ($ordersByStatus as $status => $count)
                            <tr><td>{{ $status }}</td><td class="text-end fw-semibold">{{ $count }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </x-admin.card>
        </div>
    </div>

    <x-admin.card title="Top products" subtitle="Products generating the most revenue." class="mt-3" flush>
        <table class="table admin-table mb-0">
            <thead><tr><th>Product</th><th class="text-end">Units sold</th><th class="text-end">Revenue</th></tr></thead>
            <tbody>
                @forelse ($topProducts as $product)
                    <tr>
                        <td class="text-body-emphasis">{{ $product['name'] }}</td>
                        <td class="text-end">{{ $product['quantity'] }}</td>
                        <td class="text-end fw-semibold">{{ money($product['revenue']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-body-tertiary py-4">No sales data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.card>
@endsection
