@extends('admin.layouts.app')

@section('title', 'Payments — gobuy admin')
@section('page-title', 'Payments')

@section('content')
    <x-admin.page-header title="Payments" subtitle="Review settled, pending, and failed transactions in one place.">
        <x-slot:actions>
            <span class="badge badge-phoenix badge-phoenix-secondary">{{ $payments->total() }} total</span>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table
        :cols="[
            ['label' => 'Reference'],
            ['label' => 'Order'],
            ['label' => 'Amount', 'align' => 'end'],
            ['label' => 'Status'],
            ['label' => 'Paid at'],
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

        @forelse ($payments as $payment)
            <tr>
                <td class="text-body-emphasis">{{ $payment->reference }}</td>
                <td>
                    @if ($payment->order)
                        <a href="{{ route('admin.orders.show', $payment->order) }}" class="text-decoration-none">{{ $payment->order->order_number }}</a>
                    @else — @endif
                </td>
                <td class="fw-semibold text-end">₦{{ number_format($payment->amount, 2) }}</td>
                <td>
                    <span class="badge badge-phoenix {{ ['success' => 'badge-phoenix-success', 'failed' => 'badge-phoenix-danger', 'pending' => 'badge-phoenix-warning'][$payment->status] ?? 'badge-phoenix-secondary' }}">
                        {{ ucfirst($payment->status) }}
                    </span>
                </td>
                <td>{{ $payment->paid_at?->format('M j, Y g:i A') ?? '—' }}</td>
            </tr>
        @endforelse
    </x-admin.table>

    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
