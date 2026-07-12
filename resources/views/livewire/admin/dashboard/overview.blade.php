<div>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-3">
            <x-admin.stat-card label="Revenue (paid)" value="{{ money($metrics['revenue']) }}" icon="fa-naira-sign" tone="success" />
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <x-admin.stat-card label="Paid orders" :value="number_format($metrics['paid_orders'])" icon="fa-receipt" tone="primary" hint="{{ $metrics['pending_orders'] }} awaiting payment" />
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <x-admin.stat-card label="Customers" :value="number_format($metrics['customers'])" icon="fa-users" tone="info" />
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <x-admin.stat-card label="Products" :value="number_format($metrics['products'])" icon="fa-box" tone="warning" hint="{{ $metrics['low_stock'] }} low on stock" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <x-admin.card title="Recent orders" flush>
                <x-slot:cardActions>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-link btn-sm p-0">View all</a>
                </x-slot:cardActions>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td><a href="{{ route('admin.orders.show', $order) }}" class="fw-semibold text-body-emphasis text-decoration-none">{{ $order->order_number }}</a></td>
                                    <td>{{ $order->customer_name }}</td>
                                    <td><x-admin.status-badge :value="$order->status" :label="$order->status->label()" /></td>
                                    <td class="text-end fw-semibold">{{ money($order->total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-admin.empty-state icon="fa-receipt" text="No orders yet." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>
        <div class="col-12 col-xl-4">
            <x-admin.card title="Low stock" flush>
                <div class="table-responsive">
                    <table class="table admin-table mb-0">
                        <tbody>
                            @forelse ($lowStock as $product)
                                <tr>
                                    <td>{{ $product->name }}<br><span class="fs-10 text-body-tertiary">{{ $product->category->name }}</span></td>
                                    <td class="text-end"><x-admin.status-badge :value="$product->stock == 0 ? 'failed' : 'pending'" label="{{ $product->stock }} left" /></td>
                                </tr>
                            @empty
                                <tr><td><x-admin.empty-state icon="fa-box" text="Stock looks healthy." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>
    </div>
</div>
