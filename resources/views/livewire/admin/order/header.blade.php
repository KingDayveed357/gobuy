<div class="mb-9">
    <div class="d-sm-flex flex-between-center mb-3">
        <h2 class="mb-0">Order <span>#{{ $order->order_number }}</span></h2>
        <div class="text-end">
            @if (auth('admin')->user()?->can('manage_payments') && $order->isPaid())
                <button type="button" class="btn btn-sm btn-phoenix-danger me-2" data-bs-toggle="modal" data-bs-target="#refundModal">
                    <span class="fas fa-reply me-2"></span>Issue refund
                </button>
            @endif
            <a
                href="{{ route('admin.orders.print', $order) }}"
                target="_blank"
                class="btn btn-sm btn-phoenix-secondary me-2"
                title="Open packing slip in new tab"
            >
                <span class="fas fa-print me-1"></span>Print order
            </a>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-phoenix-secondary">
                <span class="fas fa-chevron-left me-2"></span>All orders
            </a>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-3">
        <p class="text-body-tertiary mb-0">Placed on {{ $order->placed_at?->format('M j, Y g:i A') ?? $order->created_at->format('M j, Y g:i A') }}</p>
        <span class="badge badge-phoenix badge-phoenix-info fs-9">{{ $order->status->label() }}</span>
        @if ($order->hasReturns())
            <span class="badge badge-phoenix badge-phoenix-secondary fs-9" title="Items returned">{{ $order->returnStateLabel() }}</span>
        @endif
    </div>
</div>
