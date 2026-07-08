@if ($orders->isEmpty())
    <div class="card border-0 shadow-sm text-center py-6">
        <div class="card-body">
            <span class="fas fa-shopping-bag fs-1 text-body-tertiary mb-3"></span>
            <h5 class="text-body-emphasis mb-2">No orders yet</h5>
            <p class="text-body-tertiary mb-4">When you place an order, it will appear here.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary">Start shopping</a>
        </div>
    </div>
@else
    {{-- Desktop Table Layout --}}
    <div class="card border-0 shadow-sm d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-sm fs-9 mb-0">
                <thead class="bg-body-highlight">
                    <tr>
                        <th class="ps-3 border-0 py-3">Order</th>
                        <th class="border-0 py-3">Date</th>
                        <th class="border-0 py-3 text-center">Items</th>
                        <th class="border-0 py-3">Status</th>
                        <th class="text-end border-0 py-3">Total</th>
                        <th class="text-end pe-3 border-0 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="ps-3 py-3 align-middle">
                                <a href="{{ route('orders.success', $order) }}" class="fw-semibold text-body-emphasis text-decoration-none">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="py-3 align-middle text-body-tertiary">{{ $order->placed_at?->format('M j, Y') }}</td>
                            <td class="py-3 align-middle text-center text-body-tertiary">{{ $order->items->count() }}</td>
                            <td class="py-3 align-middle">
                                <span class="badge badge-phoenix {{ $order->isPaid() ? 'badge-phoenix-success' : 'badge-phoenix-warning' }} rounded-pill">
                                    {{ $order->status->label() }}
                                </span>
                                @if ($order->hasReturns())
                                    <span class="badge badge-phoenix badge-phoenix-secondary rounded-pill ms-1" title="Items returned">{{ $order->returnStateLabel() }}</span>
                                @endif
                            </td>
                            <td class="text-end py-3 align-middle fw-semibold text-body-emphasis">{{ money($order->total) }}</td>
                            <td class="text-end pe-3 py-3 align-middle">
                                <div class="d-flex justify-content-end gap-2">
                                    @if ($order->status->value === 'pending' && ! $order->isPaid() && $order->payment_method === \App\Modules\Order\Enums\PaymentMethod::Paystack)
                                        <form action="{{ route('orders.retry', $order) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-primary" title="Finish paying for this order">Pay now</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('account.orders.reorder.preview', $order) }}" class="btn btn-sm btn-phoenix-secondary" title="Reorder these items"><span class="fas fa-rotate-right"></span></a>
                                    <a href="{{ route('orders.success', $order) }}" class="btn btn-sm btn-phoenix-secondary">Details</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile Cards Layout --}}
    <div class="d-md-none d-flex flex-column gap-3">
        @foreach ($orders as $order)
            <div class="card border-translucent shadow-none rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <a href="{{ route('orders.success', $order) }}" class="fw-bold text-body-emphasis text-decoration-none fs-8">
                            {{ $order->order_number }}
                        </a>
                        <span class="badge badge-phoenix {{ $order->isPaid() ? 'badge-phoenix-success' : 'badge-phoenix-warning' }} rounded-pill fs-10">
                            {{ $order->status->label() }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 fs-9 text-body-secondary">
                        <span>{{ $order->placed_at?->format('M j, Y') }} &bull; {{ $order->items->count() }} item(s)</span>
                        <span class="fw-semibold text-body-emphasis">{{ money($order->total) }}</span>
                    </div>
                    
                    <div class="d-flex flex-column gap-2">
                        @if ($order->status->value === 'pending' && ! $order->isPaid() && $order->payment_method === \App\Modules\Order\Enums\PaymentMethod::Paystack)
                            <form action="{{ route('orders.retry', $order) }}" method="POST">
                                @csrf
                                <button class="btn btn-primary btn-sm w-100">Pay now</button>
                            </form>
                        @endif
                        <div class="d-flex gap-2">
                            <a href="{{ route('orders.success', $order) }}" class="btn btn-phoenix-secondary btn-sm flex-1">View Details</a>
                            <a href="{{ route('account.orders.reorder.preview', $order) }}" class="btn btn-phoenix-secondary btn-sm flex-1"><span class="fas fa-rotate-right me-1"></span> Reorder</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
