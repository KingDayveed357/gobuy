@extends('admin.layouts.app')

@section('title', $order->reference.' — Quintessential Mart admin')
@section('page-title', 'Purchase order')

@section('content')
    <x-admin.page-header :title="$order->reference" :subtitle="'Raised '.$order->created_at->format('M j, Y').($order->supplier ? ' · '.$order->supplier->name : '')">
        <x-slot:actions>
            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-arrow-left me-1"></span>All orders</a>
            @if ($order->status === \App\Modules\Operations\Purchasing\Enums\PurchaseOrderStatus::Draft)
                <form method="POST" action="{{ route('admin.purchase-orders.place', $order) }}" class="d-inline">@csrf
                    <button class="btn btn-primary btn-sm"><span class="fas fa-paper-plane me-1"></span>Place order</button>
                </form>
            @endif
            @if (in_array($order->status, [\App\Modules\Operations\Purchasing\Enums\PurchaseOrderStatus::Draft, \App\Modules\Operations\Purchasing\Enums\PurchaseOrderStatus::Ordered], true))
                <form method="POST" action="{{ route('admin.purchase-orders.cancel', $order) }}" class="d-inline" onsubmit="return confirm('Cancel this purchase order?');">@csrf
                    <button class="btn btn-phoenix-danger btn-sm"><span class="fas fa-ban me-1"></span>Cancel</button>
                </form>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('error'))<div class="alert alert-subtle-danger">{{ session('error') }}</div>@endif

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <x-admin.card title="Items" flush>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>Product</th><th>SKU</th><th class="text-end">Unit cost</th><th class="text-end">Ordered</th><th class="text-end">Received</th><th class="text-end">Line cost</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="fw-semibold fs-9">{{ $item->variant?->product?->name }}@if ($item->variant && ! $item->variant->is_default) — {{ $item->variant->label() }}@endif</td>
                                    <td class="fs-10 text-body-tertiary">{{ $item->variant?->sku }}</td>
                                    <td class="text-end fs-9">{{ $item->unit_cost->format() }}</td>
                                    <td class="text-end fs-9">{{ $item->quantity_ordered }}</td>
                                    <td class="text-end fs-9">{{ $item->quantity_received }}@if ($item->outstanding() > 0)<span class="text-body-tertiary"> / {{ $item->outstanding() }} left</span>@endif</td>
                                    <td class="text-end fw-semibold">{{ $item->lineCost()->format() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-top"><td colspan="5" class="text-end fw-semibold">Total</td><td class="text-end fw-bold">{{ $order->total()->format() }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </x-admin.card>

            @if ($order->status->canReceive())
                <div class="mt-4">
                    <livewire:admin.purchasing.receive-goods :order="$order" />
                </div>
            @endif
        </div>

        <div class="col-12 col-lg-4">
            <x-admin.card title="Details">
                <dl class="row mb-0 fs-9">
                    <dt class="col-5 text-body-tertiary fw-normal">Status</dt>
                    <dd class="col-7 text-end"><span class="badge badge-phoenix badge-phoenix-{{ $order->status->tone() }}">{{ $order->status->label() }}</span></dd>
                    <dt class="col-5 text-body-tertiary fw-normal">Supplier</dt>
                    <dd class="col-7 text-end">{{ $order->supplier?->name ?? '—' }}</dd>
                    <dt class="col-5 text-body-tertiary fw-normal">Deliver to</dt>
                    <dd class="col-7 text-end">{{ $order->location?->name }}</dd>
                    <dt class="col-5 text-body-tertiary fw-normal">Raised by</dt>
                    <dd class="col-7 text-end">{{ $order->createdBy?->name ?? '—' }}</dd>
                    @if ($order->ordered_at)
                        <dt class="col-5 text-body-tertiary fw-normal">Placed</dt>
                        <dd class="col-7 text-end">{{ $order->ordered_at->format('M j, Y') }}</dd>
                    @endif
                    @if ($order->received_at)
                        <dt class="col-5 text-body-tertiary fw-normal">Received</dt>
                        <dd class="col-7 text-end">{{ $order->received_at->format('M j, Y') }}</dd>
                    @endif
                </dl>
                @if ($order->note)
                    <hr class="my-3">
                    <p class="fs-9 text-body-secondary mb-0">{{ $order->note }}</p>
                @endif
            </x-admin.card>
        </div>
    </div>
@endsection
