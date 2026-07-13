@extends('admin.layouts.app')

@section('title', 'Purchase orders — gobuy admin')
@section('page-title', 'Purchase orders')

@section('content')
    <x-admin.page-header title="Purchase orders" subtitle="Order stock from your suppliers and receive it into a location. Every receipt lands in the inventory ledger.">
        <x-slot:actions>
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-truck-field me-1"></span>Suppliers</a>
            <a href="{{ route('admin.purchase-orders.create') }}" class="btn btn-primary btn-sm"><span class="fas fa-plus me-1"></span>New purchase order</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card flush>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead><tr><th>Reference</th><th>Supplier</th><th>Deliver to</th><th class="text-end">Items</th><th class="text-end">Value</th><th>Status</th><th>Raised</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.purchase-orders.show', $order) }}" class="fw-semibold text-body-emphasis text-decoration-none">{{ $order->reference }}</a></td>
                            <td class="fs-9">{{ $order->supplier?->name ?? '—' }}</td>
                            <td class="fs-9">{{ $order->location?->name }}</td>
                            <td class="text-end fs-9">{{ (int) $order->items_count }}</td>
                            <td class="text-end fw-semibold">{{ $order->total()->format() }}</td>
                            <td><span class="badge badge-phoenix badge-phoenix-{{ $order->status->tone() }}">{{ $order->status->label() }}</span></td>
                            <td class="fs-10 text-body-tertiary">{{ $order->created_at->format('M j, Y') }}</td>
                            <td class="text-end">
                                <div class="table-actions justify-content-end">
                                    <a href="{{ route('admin.purchase-orders.show', $order) }}" class="btn btn-sm btn-phoenix-secondary" aria-label="View order"><span class="fas fa-eye"></span></a>
                                    @if ($order->status === \App\Modules\Operations\Purchasing\Enums\PurchaseOrderStatus::Draft)
                                        <a href="{{ route('admin.purchase-orders.edit', $order) }}" class="btn btn-sm btn-phoenix-primary" aria-label="Edit order"><span class="fas fa-pen"></span></a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-phoenix-secondary" disabled aria-label="Cannot edit placed order"><span class="fas fa-pen"></span></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-admin.empty-state icon="fa-file-invoice" text="No purchase orders yet — raise one to restock." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    @if ($orders->hasPages())
        <div class="mt-3">{{ $orders->links() }}</div>
    @endif
@endsection
