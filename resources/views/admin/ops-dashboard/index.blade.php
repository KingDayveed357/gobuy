@extends('admin.layouts.app')

@section('title', 'Operations — Quintessential Mart admin')
@section('page-title', 'Operations')

@section('content')
    <x-admin.page-header title="Operations" subtitle="How the business is moving — stock on hand, sales by channel and your fastest movers over the last 30 days." />

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4"><x-admin.card fill><x-admin.stat-card label="Stock value" :value="$totals['value']->format()" icon="fa-coins" tone="primary" hint="Retail value of all on-hand stock" /></x-admin.card></div>
        <div class="col-12 col-md-4"><x-admin.card fill><x-admin.stat-card label="Units on hand" :value="number_format($totals['units'])" icon="fa-boxes-stacked" tone="info" :hint="number_format($totals['skus']).' active SKUs'" /></x-admin.card></div>
        <div class="col-12 col-md-4"><x-admin.card fill><x-admin.stat-card label="Locations" :value="$byLocation->count()" icon="fa-map-pin" tone="success" hint="Places holding stock" /></x-admin.card></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <x-admin.card title="Stock by location" flush>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>Location</th><th class="text-end">SKUs</th><th class="text-end">Units</th><th class="text-end">Value</th></tr></thead>
                        <tbody>
                            @forelse ($byLocation as $row)
                                <tr>
                                    <td class="fw-semibold fs-9">{{ $row['name'] }}</td>
                                    <td class="text-end fs-9">{{ number_format($row['skus']) }}</td>
                                    <td class="text-end fs-9">{{ number_format($row['units']) }}</td>
                                    <td class="text-end fw-semibold">{{ $row['value']->format() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-admin.empty-state icon="fa-map-pin" text="No stock recorded yet." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-6">
            <x-admin.card title="Sales by channel" subtitle="Paid orders · last 30 days" flush>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>Channel</th><th class="text-end">Orders</th><th class="text-end">Revenue</th></tr></thead>
                        <tbody>
                            @forelse ($salesByChannel as $row)
                                <tr>
                                    <td class="fw-semibold fs-9">{{ $row['label'] }}</td>
                                    <td class="text-end fs-9">{{ number_format($row['orders']) }}</td>
                                    <td class="text-end fw-semibold">{{ $row['revenue']->format() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><x-admin.empty-state icon="fa-store" text="No paid sales in this period." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-6">
            <x-admin.card title="Top movers" subtitle="Units sold · last 30 days" flush>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>Product</th><th>SKU</th><th class="text-end">Sold</th></tr></thead>
                        <tbody>
                            @forelse ($topMovers as $row)
                                <tr>
                                    <td class="fw-semibold fs-9">{{ $row['product'] }}</td>
                                    <td class="fs-10 text-body-tertiary">{{ $row['sku'] }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($row['units']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><x-admin.empty-state icon="fa-arrow-trend-up" text="No sales movement yet." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-6">
            <x-admin.card title="Low stock" subtitle="At or below 5 units" flush>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>Product</th><th>SKU</th><th class="text-end">On hand</th></tr></thead>
                        <tbody>
                            @forelse ($lowStock as $variant)
                                <tr>
                                    <td class="fw-semibold fs-9">{{ $variant->product?->name }}@if (! $variant->is_default) — {{ $variant->label() }}@endif</td>
                                    <td class="fs-10 text-body-tertiary">{{ $variant->sku }}</td>
                                    <td class="text-end fw-bold {{ $variant->stock <= 0 ? 'text-danger' : 'text-warning' }}">{{ number_format($variant->stock) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><x-admin.empty-state icon="fa-circle-check" text="Nothing running low." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>
    </div>
@endsection
