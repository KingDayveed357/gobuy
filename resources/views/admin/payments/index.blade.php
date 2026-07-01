@extends('admin.layouts.app')

@section('title', 'Payments — gobuy admin')
@section('page-title', 'Payments')

@section('content')
    <x-admin.page-header title="Payments" subtitle="Review settled, pending, and failed transactions — and reconcile stuck payments.">
        <x-slot:actions>
            <a href="{{ route('admin.transfers.index') }}" class="btn btn-phoenix-secondary"><span class="fas fa-building-columns me-2"></span>Bank transfers</a>
            <a href="{{ route('admin.reconciliation') }}" class="btn btn-phoenix-secondary"><span class="fas fa-scale-balanced me-2"></span>Daily report</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Pending" value="{{ $stats['pending_count'] }}" icon="fa-hourglass-half" tone="warning" hint="Awaiting confirmation" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Pending value" value="{{ money($stats['pending_value']) }}" icon="fa-coins" tone="info" hint="Not yet collected" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Succeeded" value="{{ $stats['success_count'] }}" icon="fa-circle-check" tone="success" /></div>
        <div class="col-6 col-lg-3"><x-admin.stat-card label="Failed" value="{{ $stats['failed_count'] }}" icon="fa-circle-xmark" tone="danger" /></div>
    </div>

    <x-admin.table
        :cols="[
            ['label' => 'Reference'],
            ['label' => 'Order'],
            ['label' => 'Amount', 'align' => 'end'],
            ['label' => 'Status'],
            ['label' => 'Paid at'],
            ['label' => 'Actions', 'align' => 'end'],
        ]"
        :empty="$payments->isEmpty()"
        empty-icon="fa-credit-card"
        empty-text="No payments yet."
    >
        <x-slot:toolbar>
            <form method="GET" class="admin-toolbar mb-0 w-100">
                <div class="admin-toolbar-grow" style="max-width: 340px;">
                    <div class="position-relative">
                        <span class="fas fa-search position-absolute text-body-tertiary" style="top: 50%; left: 0.85rem; transform: translateY(-50%);"></span>
                        <input class="form-control form-control-sm ps-5" type="search" name="q" value="{{ request('q') }}" placeholder="Search by reference">
                    </div>
                </div>
                <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'success', 'failed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-phoenix-secondary" type="submit">Filter</button>
            </form>
        </x-slot:toolbar>

        @foreach ($payments as $payment)
            <tr>
                <td class="text-body-emphasis">{{ $payment->reference }}</td>
                <td>
                    @if ($payment->order)
                        <a href="{{ route('admin.orders.show', $payment->order) }}" class="text-decoration-none">{{ $payment->order->order_number }}</a>
                        <span class="d-block fs-10 text-body-tertiary">{{ $payment->order->customer_name }}</span>
                    @else — @endif
                </td>
                <td class="fw-semibold text-end">{{ money($payment->amount) }}</td>
                <td>
                    <span class="badge badge-phoenix {{ ['success' => 'badge-phoenix-success', 'failed' => 'badge-phoenix-danger', 'pending' => 'badge-phoenix-warning'][$payment->status] ?? 'badge-phoenix-secondary' }}">
                        {{ ucfirst($payment->status) }}
                    </span>
                </td>
                <td>{{ $payment->paid_at?->format('M j, Y g:i A') ?? '—' }}</td>
                <td class="text-end">
                    @if ($payment->status === 'pending')
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-phoenix-info"
                                data-bs-toggle="modal" data-bs-target="#actionModal"
                                data-action="{{ route('admin.payments.verify', $payment) }}" data-method="POST"
                                data-title="Verify with Paystack"
                                data-message="Re-check this payment with Paystack and complete the order if the charge succeeded?"
                                data-confirm-text="Verify now" data-variant="info">Verify</button>
                            <button type="button" class="btn btn-phoenix-success"
                                data-bs-toggle="modal" data-bs-target="#actionModal"
                                data-action="{{ route('admin.payments.mark-paid', $payment) }}" data-method="POST"
                                data-title="Mark payment as paid"
                                data-message="Manually mark this payment PAID? This completes the order, decrements stock and applies any coupon/store credit. Only do this once you have confirmed the funds were received."
                                data-confirm-text="Yes, mark as paid" data-variant="success">Mark paid</button>
                            <button type="button" class="btn btn-phoenix-danger"
                                data-bs-toggle="modal" data-bs-target="#actionModal"
                                data-action="{{ route('admin.payments.mark-failed', $payment) }}" data-method="POST"
                                data-title="Mark payment as failed"
                                data-message="Mark this pending payment as FAILED? The order will be flagged unpaid. No stock is affected."
                                data-confirm-text="Yes, mark as failed" data-variant="danger">Mark failed</button>
                        </div>
                    @else
                        <span class="text-body-tertiary">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
