@extends('admin.layouts.app')

@section('title', 'Orders — gobuy admin')
@section('page-title', 'Orders')

@section('content')
    <x-admin.page-header title="Orders" subtitle="{{ $orders->total() }} order(s)" />

    <x-admin.table
        :cols="[
            ['label' => 'Order'],
            ['label' => 'Date'],
            ['label' => 'Customer'],
            ['label' => 'Items', 'align' => 'center'],
            ['label' => 'Payment'],
            ['label' => 'Status'],
            ['label' => 'Total', 'align' => 'end'],
            ['label' => '', 'align' => 'end'],
        ]"
        :empty="$orders->isEmpty()"
        empty-icon="fa-receipt"
        empty-text="No orders found."
    >
        <x-slot:toolbar>
            <form method="GET" class="admin-toolbar mb-0 w-100">
                <div class="admin-toolbar-grow" style="max-width: 340px;">
                    <div class="position-relative">
                        <span class="fas fa-search position-absolute text-body-tertiary" style="top: 50%; left: 0.85rem; transform: translateY(-50%);"></span>
                        <input class="form-control form-control-sm ps-5" type="search" name="q" value="{{ request('q') }}" placeholder="Search order #, name or email">
                    </div>
                </div>
                <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-phoenix-secondary" type="submit">Filter</button>
            </form>
        </x-slot:toolbar>

        @foreach ($orders as $order)
            <tr>
                <td><a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold text-body-emphasis text-decoration-none">{{ $order->order_number }}</a></td>
                <td>{{ $order->placed_at?->format('M j, Y') }}</td>
                <td>{{ $order->customer_name }}<br><span class="fs-10 text-body-tertiary">{{ $order->customer_email }}</span></td>
                <td class="text-center">{{ $order->items_count }}</td>
                <td><x-admin.status-badge :value="$order->payment_status" :label="$order->payment_status->label()" /></td>
                <td><x-admin.status-badge :value="$order->status" :label="$order->status->label()" /></td>
                <td class="text-end fw-semibold">₦{{ number_format($order->total, 2) }}</td>
                <td class="text-end"><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-phoenix-secondary">View</a></td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
