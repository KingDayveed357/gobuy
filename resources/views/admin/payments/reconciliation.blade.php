@extends('admin.layouts.app')

@section('title', 'Reconciliation — gobuy admin')
@section('page-title', 'Reconciliation')

@section('content')
    <x-admin.page-header title="Daily reconciliation" subtitle="Orders vs collections for {{ $date->format('M j, Y') }}">
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date->toDateString() }}">
                <button class="btn btn-sm btn-primary">View</button>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Orders placed" value="{{ money($ordersTotal) }}" icon="fa-receipt" tone="primary" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Collected" value="{{ money($collected) }}" icon="fa-circle-check" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Outstanding" value="{{ money($outstanding) }}" icon="fa-hourglass-half" tone="warning" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Paystack settled" value="{{ money($paystackSettled) }}" icon="fa-credit-card" tone="info" /></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <x-admin.card title="By payment method">
                <div class="table-responsive">
                    <table class="table admin-table fs-9 mb-0">
                        <thead><tr><th>Method</th><th class="text-center">Orders</th><th class="text-end">Value</th></tr></thead>
                        <tbody>
                            @foreach ($byMethod as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['label'] }}</td>
                                    <td class="text-center">{{ $row['count'] }}</td>
                                    <td class="text-end">{{ money($row['total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>
        <div class="col-12 col-lg-6">
            <x-admin.card title="Settlement check">
                <div class="d-flex justify-content-between border-bottom border-translucent py-2"><span class="text-body-tertiary">Paystack settlements</span><span class="fw-semibold">{{ money($paystackSettled) }}</span></div>
                <div class="d-flex justify-content-between border-bottom border-translucent py-2"><span class="text-body-tertiary">Bank transfers confirmed</span><span class="fw-semibold">{{ money($bankConfirmed) }}</span></div>
                <div class="d-flex justify-content-between py-2"><span class="fw-semibold">Total collected</span><span class="fw-bold">{{ money($collected) }}</span></div>
                <p class="fs-10 text-body-tertiary mb-0 mt-2">Compare the figures above against your bank statement and Paystack dashboard for {{ $date->format('M j, Y') }}.</p>
            </x-admin.card>
        </div>
    </div>
@endsection
