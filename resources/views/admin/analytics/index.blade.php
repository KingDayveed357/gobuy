@extends('admin.layouts.app')

@section('title', 'Analytics — gobuy admin')
@section('page-title', 'Analytics')

@section('content')
    <x-admin.page-header title="Executive analytics" subtitle="Strategic insights for the last {{ $period }} days — not accounting reconciliation.">
        <x-slot:actions>
            <div class="btn-group btn-group-sm" role="group" aria-label="Period">
                @foreach ([7, 14, 30, 90] as $option)
                    <a href="{{ route('admin.analytics', ['period' => $option]) }}"
                       @class(['btn', 'btn-phoenix-secondary' => $period !== $option, 'btn-primary' => $period === $option])>{{ $option }}d</a>
                @endforeach
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- ── Tier 1: Executive KPIs ──────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <x-admin.stat-card label="Revenue (paid)" value="{{ money($totals['revenue']) }}" icon="fa-naira-sign" tone="success"
                hint="{{ $totals['growth_rate'] >= 0 ? '+' : '' }}{{ $totals['growth_rate'] }}% vs prior period" />
        </div>
        <div class="col-6 col-lg-3">
            <x-admin.stat-card label="Paid orders" :value="number_format($totals['paid_orders'])" icon="fa-receipt" tone="primary"
                hint="{{ $comparison['orders_growth'] >= 0 ? '+' : '' }}{{ $comparison['orders_growth'] }}% vs prior" />
        </div>
        <div class="col-6 col-lg-3">
            <x-admin.stat-card label="Avg order value" value="{{ money($totals['average_order_value']) }}" icon="fa-chart-line" tone="info" />
        </div>
        <div class="col-6 col-lg-3">
            <x-admin.stat-card label="Payment success" :value="$paymentSuccessRate.'%'" icon="fa-credit-card" tone="warning"
                hint="{{ money($totals['refunded']) }} refunded" />
        </div>
    </div>

    {{-- ── Executive Business Summary ──────────────────────────────────────────── --}}
    @if (! empty($insights))
        <div class="card border border-translucent mb-4" style="border-radius: 0.85rem; overflow: hidden;">
            <div class="card-body p-0">
                <div class="d-flex align-items-center px-4 py-3 border-bottom border-translucent">
                    <span class="fas fa-circle-info text-primary me-2 fs-8"></span>
                    <h6 class="mb-0 text-body-emphasis fw-semibold">Business summary</h6>
                    <span class="ms-auto badge badge-phoenix badge-phoenix-primary fs-10">{{ $period }}d snapshot</span>
                </div>
                <div class="row g-0">
                    @foreach ($insights as $i => $insight)
                        <div @class(['col-12 col-lg-6', 'border-bottom border-translucent' => $i < count($insights) - 2 || (count($insights) % 2 !== 0 && $i === count($insights) - 1)])>
                            <div class="d-flex align-items-start gap-3 px-4 py-3 {{ ($i % 2 === 0 && $i + 1 < count($insights)) ? 'border-end-lg border-translucent' : '' }}">
                                @php
                                    $icon = match($insight['type']) {
                                        'success' => 'fa-circle-check text-success',
                                        'danger'  => 'fa-circle-exclamation text-danger',
                                        'warning' => 'fa-triangle-exclamation text-warning',
                                        default   => 'fa-lightbulb text-primary',
                                    };
                                @endphp
                                <span class="fas {{ $icon }} mt-1 flex-shrink-0"></span>
                                <div class="min-w-0">
                                    <p class="fs-9 fw-semibold text-body-emphasis mb-0">{{ $insight['title'] }}</p>
                                    <p class="fs-10 text-body-tertiary mb-0 mt-1">{{ $insight['message'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ── Detail sections behind tabs (progressive disclosure) ─────────────────── --}}
    <ul class="nav nav-underline mb-3" id="analyticsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-btn-revenue" data-bs-toggle="tab" data-bs-target="#tab-revenue" type="button" role="tab" aria-controls="tab-revenue" aria-selected="true">
                <span class="fas fa-chart-line me-2"></span>Revenue &amp; orders
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-btn-products" data-bs-toggle="tab" data-bs-target="#tab-products" type="button" role="tab" aria-controls="tab-products" aria-selected="false">
                <span class="fas fa-box me-2"></span>Products
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-btn-customers" data-bs-toggle="tab" data-bs-target="#tab-customers" type="button" role="tab" aria-controls="tab-customers" aria-selected="false">
                <span class="fas fa-users me-2"></span>Customers
            </button>
        </li>
    </ul>

    <div class="tab-content" id="analyticsTabContent">
    {{-- ── Tab: Revenue & Orders ────────────────────────────────────────────────── --}}
    <div class="tab-pane fade show active" id="tab-revenue" role="tabpanel" aria-labelledby="tab-btn-revenue" tabindex="0">
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <x-admin.card title="Revenue intelligence" subtitle="Daily paid revenue vs the equivalent prior-period window." class="h-100">
                <div id="chartRevenueTrend" style="min-height: 300px;"></div>
            </x-admin.card>
        </div>
        <div class="col-12 col-xl-4">
            <x-admin.card title="Order pipeline" subtitle="Current distribution by lifecycle status." class="h-100">
                <div id="chartOrderStatus" style="min-height: 350px;"></div>
            </x-admin.card>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <x-admin.card title="Revenue forecast" subtitle="7-day projection from recent daily average (dashed line).">
                <div id="chartRevenueForecast" style="min-height: 260px;"></div>
            </x-admin.card>
        </div>
        <div class="col-12 col-xl-6">
            <x-admin.card title="Order intelligence" subtitle="Conversion, cancellation, and refund rates for the selected period.">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="border border-translucent rounded-3 p-3 text-center h-100">
                            <p class="fs-10 text-body-tertiary mb-1">Conversion</p>
                            <h4 class="mb-0 text-body-emphasis">{{ $orderRates['conversion_rate'] }}%</h4>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border border-translucent rounded-3 p-3 text-center h-100">
                            <p class="fs-10 text-body-tertiary mb-1">Cancellation</p>
                            <h4 class="mb-0 text-body-emphasis">{{ $orderRates['cancellation_rate'] }}%</h4>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border border-translucent rounded-3 p-3 text-center h-100">
                            <p class="fs-10 text-body-tertiary mb-1">Refund rate</p>
                            <h4 class="mb-0 text-body-emphasis">{{ $orderRates['refund_rate'] }}%</h4>
                        </div>
                    </div>
                </div>
                <div id="chartFailedPayments" class="mt-4" style="min-height: 180px;"></div>
            </x-admin.card>
        </div>
    </div>

    </div>{{-- /tab-revenue --}}

    {{-- ── Tab: Products ────────────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="tab-products" role="tabpanel" aria-labelledby="tab-btn-products" tabindex="0">
    @php
        $tpRevenueTotal = (float) $totals['revenue'];
        $shownRevenue = (float) $topProducts->sum(fn ($p) => (float) $p['revenue']);
        $topShare = $tpRevenueTotal > 0 ? (int) round($shownRevenue / $tpRevenueTotal * 100) : 0;

        $revenueItems = $topProducts->map(fn ($p) => [
            'label' => $p['name'],
            'value' => (float) $p['revenue'],
            'sub' => number_format($p['quantity']).' units',
        ])->values()->all();

        // Aggregate the long tail so the ranking never renders hundreds of rows.
        $othersRevenue = max(0, $tpRevenueTotal - $shownRevenue);
        if ($othersRevenue > 0) {
            $revenueItems[] = ['label' => 'All other products', 'value' => $othersRevenue, 'sub' => '', 'others' => true];
        }

        $unitItems = $topProductsByUnits->map(fn ($p) => [
            'label' => $p['name'],
            'value' => (float) $p['quantity'],
            'sub' => money($p['revenue']),
        ])->values()->all();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <x-admin.card flush class="h-100">
                <div x-data="{ view: 'revenue' }">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 pt-3 pb-2">
                        <div class="min-w-0">
                            <h5 class="mb-0">Top products</h5>
                            <p class="fs-9 text-body-tertiary mb-0">
                                Top {{ $topProducts->count() }} drive <strong class="text-body-emphasis">{{ $topShare }}%</strong> of paid revenue
                            </p>
                        </div>
                        <div class="btn-group btn-group-sm flex-shrink-0" role="group" aria-label="Rank products by">
                            <button type="button" class="btn" :class="view === 'revenue' ? 'btn-primary' : 'btn-phoenix-secondary'" @click="view = 'revenue'">Revenue</button>
                            <button type="button" class="btn" :class="view === 'units' ? 'btn-primary' : 'btn-phoenix-secondary'" @click="view = 'units'">Units</button>
                        </div>
                    </div>
                    <div class="px-3 pb-3">
                        <div x-show="view === 'revenue'">
                            <x-admin.ranked-bar-list :items="$revenueItems" :total="$tpRevenueTotal" format="money" empty-text="No sales data yet for this period." empty-icon="fa-box" />
                        </div>
                        <div x-show="view === 'units'" x-cloak>
                            <x-admin.ranked-bar-list :items="$unitItems" format="number" empty-text="No sales data yet for this period." empty-icon="fa-box" />
                        </div>
                    </div>
                </div>
            </x-admin.card>
        </div>
        <div class="col-12 col-xl-6">
            <x-admin.card title="Category performance" subtitle="Revenue contribution by category." class="h-100">
                <div id="chartTopCategories" style="min-height: 280px;"></div>
            </x-admin.card>
        </div>
    </div>

    </div>{{-- /tab-products --}}

    {{-- ── Tab: Customers ───────────────────────────────────────────────────────── --}}
    <div class="tab-pane fade" id="tab-customers" role="tabpanel" aria-labelledby="tab-btn-customers" tabindex="0">
    {{-- Customer KPI strip --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="border border-translucent rounded-3 p-3 h-100 bg-body">
                <p class="fs-10 text-body-tertiary mb-1 text-uppercase fw-bold ls-1">All Customers</p>
                <h4 class="mb-0 text-body-emphasis fw-bold">{{ number_format($customerMix['all_time_customers']) }}</h4>
                <p class="fs-10 text-body-tertiary mb-0 mt-1">Registered accounts (all-time)</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border border-translucent rounded-3 p-3 h-100 bg-body">
                <p class="fs-10 text-body-tertiary mb-1 text-uppercase fw-bold ls-1">New</p>
                <h4 class="mb-0 text-body-emphasis fw-bold">{{ number_format($customerMix['new']) }}</h4>
                <p class="fs-10 mb-0 mt-1 {{ $customerMix['new_growth_rate'] >= 0 ? 'text-success' : 'text-danger' }}">
                    <span class="fas {{ $customerMix['new_growth_rate'] >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} me-1"></span>
                    {{ abs($customerMix['new_growth_rate']) }}% vs prior {{ $period }}d
                </p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border border-translucent rounded-3 p-3 h-100 bg-body">
                <p class="fs-10 text-body-tertiary mb-1 text-uppercase fw-bold ls-1">Returning</p>
                <h4 class="mb-0 text-body-emphasis fw-bold">{{ number_format($customerMix['returning']) }}</h4>
                <p class="fs-10 text-body-tertiary mb-0 mt-1">Repeat purchase rate: <strong>{{ $customerMix['repeat_purchase_rate'] }}%</strong></p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="border border-translucent rounded-3 p-3 h-100 bg-body">
                <p class="fs-10 text-body-tertiary mb-1 text-uppercase fw-bold ls-1">Avg LTV</p>
                <h4 class="mb-0 text-body-emphasis fw-bold">{{ money($customerMix['average_ltv']) }}</h4>
                <p class="fs-10 text-body-tertiary mb-0 mt-1">All-time avg across {{ number_format($customerMix['ltv_customer_count']) }} paying customers</p>
            </div>
        </div>
    </div>

    {{-- Customer charts + top customers --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-5">
            <x-admin.card title="Customer retention trend" subtitle="New vs returning customers — {{ $period }}-day window." class="h-100">
                <div id="chartCustomerRetention" style="min-height: 260px;"></div>
            </x-admin.card>
        </div>
        <div class="col-12 col-xl-3">
            <x-admin.card title="Customer mix" subtitle="Distribution for the selected period." class="h-100">
                <div id="chartCustomerSegment" style="min-height: 240px;"></div>
            </x-admin.card>
        </div>
        <div class="col-12 col-xl-4">
            <x-admin.card title="Top customers" subtitle="Ranked by lifetime spend." class="h-100">
                @forelse ($topCustomers as $i => $customer)
                    <div @class(['d-flex align-items-center gap-3 py-2', 'border-bottom border-translucent' => ! $loop->last])>
                        <div class="avatar avatar-m flex-shrink-0">
                            <div class="avatar-name rounded-circle bg-primary-subtle">
                                <span class="text-primary fw-bold fs-9">{{ strtoupper(substr($customer['name'], 0, 2)) }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <p class="mb-0 fw-semibold text-body-emphasis fs-9 text-truncate">{{ $customer['name'] }}</p>
                            <p class="mb-0 fs-10 text-body-tertiary">{{ $customer['total_orders'] }} {{ $customer['total_orders'] === 1 ? 'order' : 'orders' }} · {{ $customer['last_order_at'] }}</p>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <p class="mb-0 fw-semibold fs-9 text-body-emphasis">{{ money($customer['lifetime_spend']) }}</p>
                        </div>
                    </div>
                @empty
                    <x-admin.empty-state icon="fa-users" text="No customer data yet." compact />
                @endforelse
            </x-admin.card>
        </div>
    </div>
    </div>{{-- /tab-customers --}}
    </div>{{-- /tab-content --}}
@endsection

@push('scripts')
    @php
        $forecastSplit = $revenueForecast->where('forecast', false)->count();
        $chartPayload = [
            'revenue' => [
                'labels'   => $revenueByDay->pluck('label')->values()->all(),
                'values'   => $revenueByDay->pluck('value')->values()->all(),
                'previous' => $revenueByDay->pluck('previous')->values()->all(),
            ],
            'forecast' => [
                'labels'     => $revenueForecast->pluck('label')->values()->all(),
                'values'     => $revenueForecast->pluck('value')->values()->all(),
                'splitIndex' => $forecastSplit,
            ],
            'topProducts' => [
                'labels' => $topProducts->pluck('name')->values()->all(),
                'values' => $topProducts->pluck('revenue')->map(fn ($v) => (float) $v)->values()->all(),
            ],
            'topCategories' => [
                'labels' => $topCategories->pluck('name')->values()->all(),
                'values' => $topCategories->pluck('revenue')->map(fn ($v) => (float) $v)->values()->all(),
            ],
            'orderStatus' => [
                'labels' => array_keys($ordersByStatus),
                'values' => array_values($ordersByStatus),
            ],
            'retention' => [
                'labels' => $retentionTrend->pluck('label')->values()->all(),
                'series' => [
                    ['name' => 'New',       'data' => $retentionTrend->pluck('new')->values()->all()],
                    ['name' => 'Returning', 'data' => $retentionTrend->pluck('returning')->values()->all()],
                ],
            ],
            'customerSegment' => [
                'labels' => ['New', 'Returning', 'Guest'],
                'values' => [
                    $customerMix['new'],
                    $customerMix['returning'],
                    $customerMix['guest_checkouts'],
                ],
            ],
            'failedPayments' => [
                'labels' => $failedPaymentsByDay->pluck('label')->values()->all(),
                'values' => $failedPaymentsByDay->pluck('value')->values()->all(),
            ],
        ];
    @endphp
    <script>window.adminAnalytics = @json($chartPayload);</script>
    <script src="{{ asset('theme/vendors/echarts/echarts.min.js') }}"></script>
    <script src="{{ asset('theme/js/admin-analytics.js') }}"></script>
    <script>
        // Charts in a hidden tab pane initialise at 0 width; resize them the first
        // time their tab is shown so they render at full size.
        document.querySelectorAll('#analyticsTabs [data-bs-toggle="tab"]').forEach(function (btn) {
            btn.addEventListener('shown.bs.tab', function () {
                window.dispatchEvent(new Event('resize'));
            });
        });
    </script>
@endpush
