@extends('admin.layouts.app')

@section('title', 'Order '.$order->order_number.' — Quintessential Mart admin')

@section('content')
    @if (session('error'))
        <div class="alert alert-subtle-danger">{{ session('error') }}</div>
    @endif

    <livewire:admin.order.header :order="$order" />

    <div class="row g-5 gy-7">
        <div class="col-12 col-xl-8 col-xxl-9">
            <x-admin.order.items-table :order="$order" />
            <x-admin.order.customer-info :order="$order" />
            <livewire:admin.order.timeline :order="$order" />
        </div>

        <div class="col-12 col-xl-4 col-xxl-3">
            <livewire:admin.order.summary :order="$order" />
            <livewire:admin.order.actions-panel :order="$order" />
            <livewire:admin.order.payment-info :order="$order" />
        </div>
    </div>

    @if (auth('admin')->user()?->can('manage_payments') && $order->isPaid())
        <div class="modal fade" id="refundModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Issue Refund</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('admin.orders.refund', $order) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <label class="form-label fs-10">Amount (₦) <span class="text-body-tertiary">— blank = full {{ money($order->refundableRemaining()) }}</span></label>
                            <div class="input-group input-group-sm mb-3">
                                <span class="input-group-text">₦</span>
                                <input class="form-control" type="number" step="0.01" min="1" max="{{ $order->refundableRemaining()->toNaira() }}" name="amount" placeholder="{{ $order->refundableRemaining()->toNaira() }}">
                            </div>
                            <label class="form-label fs-10">Reason (optional)</label>
                            <input class="form-control form-control-sm mb-2" type="text" name="reason" placeholder="e.g. Customer requested cancellation">
                            <p class="fs-10 text-body-tertiary mt-2 mb-0">A full refund restocks items; a partial refund returns money only.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Issue refund</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
