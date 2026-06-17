@if ($orders->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <p class="text-body-tertiary mb-3">No orders yet.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary btn-sm">Start shopping</a>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0 fs-9">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th class="text-end">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td class="fw-semibold text-body-emphasis">{{ $order->order_number }}</td>
                            <td>{{ $order->placed_at?->format('M j, Y') }}</td>
                            <td>{{ $order->items->count() }}</td>
                            <td>
                                <span class="badge badge-phoenix {{ $order->isPaid() ? 'badge-phoenix-success' : 'badge-phoenix-warning' }}">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">₦{{ number_format($order->total, 2) }}</td>
                            <td class="text-end"><a href="{{ route('orders.success', $order) }}" class="btn btn-sm btn-phoenix-secondary">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
