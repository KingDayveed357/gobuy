@extends('admin.layouts.app')

@section('title', 'Order '.$order->order_number.' — gobuy admin')

@section('content')
    @if (session('error'))
        <div class="alert alert-subtle-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex flex-between-center mb-4">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-link p-0 fs-9 mb-1"><span class="fas fa-chevron-left me-1"></span>All orders</a>
            <h2 class="mb-0">Order {{ $order->order_number }}</h2>
            <p class="text-body-tertiary mb-0">{{ $order->placed_at?->format('M j, Y g:i A') }}</p>
        </div>
        <div class="text-end">
            <span class="badge badge-phoenix badge-phoenix-info fs-9 mb-1">{{ $order->status->label() }}</span><br>
            <span class="badge badge-phoenix {{ $order->isPaid() ? 'badge-phoenix-success' : 'badge-phoenix-warning' }} fs-9">
                Payment: {{ $order->payment_status->label() }}
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <x-admin.card title="Order items" subtitle="Products, quantities, and line totals." flush class="mb-4">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Unit</th><th class="text-end">Total</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="fw-semibold text-body-emphasis">{{ $item->name }}<br><span class="fs-10 text-body-tertiary fw-normal">{{ $item->sku }}</span></td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">₦{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end fw-semibold">₦{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="text-end text-body-tertiary">Subtotal</td><td class="text-end">₦{{ number_format($order->subtotal, 2) }}</td></tr>
                        <tr><td colspan="3" class="text-end text-body-tertiary">Delivery</td><td class="text-end">₦{{ number_format($order->delivery_fee, 2) }}</td></tr>
                        <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold">₦{{ number_format($order->total, 2) }}</td></tr>
                    </tfoot>
                </table>
            </x-admin.card>

            <x-admin.card title="Status history" subtitle="Every status change with notes and timestamps." >
                <div class="timeline-vertical">
                    @foreach ($order->statusHistories as $history)
                        <div class="timeline-item">
                            <div class="row g-2 align-items-center mb-3">
                                <div class="col-auto d-flex">
                                    <div class="timeline-item-bar position-relative me-3">
                                        <div class="icon-item icon-item-sm bg-success" data-bs-theme="light"><span class="fa-solid fa-check text-white fs-10"></span></div>
                                        <span class="timeline-bar border-end border-success"></span>
                                    </div>
                                </div>
                                <div class="col">
                                    <h6 class="mb-0">{{ $history->status->label() }} <span class="fs-10 text-body-tertiary fw-normal">— {{ $history->created_at->format('M j, g:i A') }}</span></h6>
                                    @if ($history->note)<p class="fs-9 text-body-secondary mb-0">{{ $history->note }}</p>@endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Update status" subtitle="Move the order through the fulfillment flow." class="mb-4">
                @if (empty($allowedTransitions))
                    <p class="text-body-tertiary fs-9 mb-0">This order is in a final state ({{ $order->status->label() }}).</p>
                @else
                    <form action="{{ route('admin.orders.status', $order) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <select name="status" class="form-select form-select-sm" required>
                                @foreach ($allowedTransitions as $next)
                                    <option value="{{ $next->value }}">{{ $next->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input class="form-control form-control-sm mb-2" type="text" name="note" placeholder="Note (optional)">
                        <button class="btn btn-sm btn-primary w-100" type="submit">Apply</button>
                    </form>
                @endif
            </x-admin.card>

            @if (auth('admin')->user()->can('manage_payments') && $order->isPaid())
                <x-admin.card title="Refund" subtitle="Issue a full refund and restock the items." class="mb-4">
                    <p class="fs-9 text-body-tertiary">Refunds the full ₦{{ number_format($order->total, 2) }} via Paystack and restocks items.</p>
                    <form action="{{ route('admin.orders.refund', $order) }}" method="POST" onsubmit="return confirm('Issue a full refund for this order?');">
                        @csrf
                        <input class="form-control form-control-sm mb-2" type="text" name="reason" placeholder="Reason (optional)">
                        <button class="btn btn-sm btn-phoenix-danger w-100" type="submit">Issue refund</button>
                    </form>
                </x-admin.card>
            @endif

            <x-admin.card title="Customer" subtitle="Shipping and contact details.">
                <p class="fs-9 mb-1 fw-semibold">{{ $order->customer_name }}
                    @if ($order->customer_type === \App\Models\User::TYPE_WHOLESALE)
                        <span class="badge badge-phoenix badge-phoenix-success">Wholesale</span>
                    @endif
                </p>
                <p class="fs-9 text-body-secondary mb-0">
                    {{ $order->customer_email }}<br>
                    {{ $order->customer_phone }}<br>
                    {{ $order->address_line }}, {{ $order->city }}, {{ $order->state }}
                </p>
            </x-admin.card>
        </div>
    </div>
@endsection
