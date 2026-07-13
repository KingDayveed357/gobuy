@extends('admin.layouts.app')

@section('title', 'Customers — Quintessential Mart admin')
@section('page-title', 'Customers')

@section('content')
    <x-admin.page-header title="Customers" subtitle="{{ $customers->total() }} customer(s)">
        <x-slot:actions>
            <a href="{{ route('admin.customers.export', request()->query()) }}" class="btn btn-phoenix-secondary"><span class="fas fa-file-csv me-2"></span>Export CSV</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table
        :cols="[
            ['label' => 'Name'],
            ['label' => 'Email'],
            ['label' => 'Type'],
            ['label' => 'Wholesale'],
            ['label' => 'Orders', 'align' => 'end'],
            ['label' => 'Joined'],
        ]"
        :empty="$customers->isEmpty()"
        empty-icon="fa-users"
        empty-text="No customers found."
    >
        <x-slot:toolbar>
            <form method="GET" class="admin-toolbar mb-0 w-100">
                <div class="admin-toolbar-grow" style="max-width: 340px;">
                    <div class="position-relative">
                        <span class="fas fa-search position-absolute text-body-tertiary" style="top: 50%; left: 0.85rem; transform: translateY(-50%);"></span>
                        <input class="form-control form-control-sm ps-5" type="search" name="q" value="{{ request('q') }}" placeholder="Search name or email">
                    </div>
                </div>
                <select name="type" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="">All types</option>
                    <option value="retail" @selected(request('type') === 'retail')>Retail</option>
                    <option value="wholesale" @selected(request('type') === 'wholesale')>Wholesale</option>
                </select>
                <button class="btn btn-sm btn-phoenix-secondary" type="submit">Filter</button>
            </form>
        </x-slot:toolbar>

        @foreach ($customers as $customer)
            <tr>
                <td class="fw-semibold text-body-emphasis">{{ $customer->name }}</td>
                <td class="text-body-tertiary">{{ $customer->email }}</td>
                <td>
                    @if ($customer->customer_type === \App\Models\User::TYPE_WHOLESALE)
                        <span class="badge badge-phoenix badge-phoenix-success">Wholesale</span>
                    @else
                        <span class="badge badge-phoenix badge-phoenix-info">Retail</span>
                    @endif
                </td>
                <td class="text-body-tertiary">{{ ucfirst($customer->wholesale_status) }}</td>
                <td class="text-end">{{ $customer->orders_count }}</td>
                <td>{{ $customer->created_at->format('M j, Y') }}</td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $customers->links() }}</div>
@endsection
