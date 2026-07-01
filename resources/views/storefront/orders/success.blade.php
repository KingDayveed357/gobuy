@extends('layouts.storefront')

@section('title', 'Order '.$order->order_number.' — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            @if (session('error'))
                <div class="alert alert-subtle-warning">{{ session('error') }}</div>
            @endif

            <div class="text-center mb-6">
                @if ($order->isPaid())
                    <span class="fas fa-circle-check text-success" style="font-size: 3rem;"></span>
                    <h2 class="mt-3 mb-1">Thank you for your order!</h2>
                    <p class="text-body-secondary">Your payment was successful and your order is confirmed.</p>
                @else
                    <span class="fas fa-clock text-warning" style="font-size: 3rem;"></span>
                    <h2 class="mt-3 mb-1">Order received</h2>
                    <p class="text-body-secondary">Your order is awaiting payment confirmation.</p>
                @endif
            </div>

            @php
                $awaitingOnlinePayment = $order->status === \App\Modules\Order\Enums\OrderStatus::Pending
                    && ! $order->isPaid()
                    && $order->payment_method === \App\Modules\Order\Enums\PaymentMethod::Paystack;
            @endphp
            @if ($awaitingOnlinePayment)
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-8">
                        <div class="alert alert-subtle-warning d-flex flex-wrap justify-content-between align-items-center gap-3 mb-0">
                            <span><span class="fas fa-clock me-2"></span>This order hasn't been paid yet — finish your payment to confirm it.</span>
                            <div class="d-flex gap-2">
                                <form action="{{ route('orders.retry', $order) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-primary"><span class="fas fa-credit-card me-1"></span>Complete payment</button>
                                </form>
                                <form action="{{ route('orders.cancel', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this order? This cannot be undone.');">
                                    @csrf
                                    <button class="btn btn-sm btn-phoenix-secondary">Cancel order</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-between-center mb-4">
                                <div>
                                    <h4 class="mb-1">Order {{ $order->order_number }}</h4>
                                    <p class="fs-9 text-body-tertiary mb-0">{{ $order->placed_at?->format('M j, Y g:i A') }}</p>
                                </div>
                                <span class="badge badge-phoenix {{ $order->isPaid() ? 'badge-phoenix-success' : 'badge-phoenix-warning' }} fs-9">
                                    {{ $order->status->label() }}
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table fs-9 mb-0">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Unit</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->items as $item)
                                            <tr>
                                                <td class="fw-semibold text-body-emphasis">{{ $item->name }}<br><span class="fs-10 text-body-tertiary fw-normal">{{ $item->sku }}</span></td>
                                                <td class="text-center">{{ $item->quantity }}</td>
                                                <td class="text-end">{{ money($item->unit_price) }}</td>
                                                <td class="text-end fw-semibold">{{ money($item->line_total) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <hr class="border-translucent">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-2">Delivery to</h6>
                                    <p class="fs-9 text-body-secondary mb-0">
                                        {{ $order->customer_name }}<br>
                                        {{ $order->address_line }}, {{ $order->city }}, {{ $order->state }}<br>
                                        {{ $order->customer_phone }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between"><span class="text-body-tertiary">Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-body-tertiary">Delivery</span><span>{{ money($order->delivery_fee) }}</span></div>
                                    @if ($order->tax_amount->isPositive())
                                        <div class="d-flex justify-content-between"><span class="text-body-tertiary">VAT</span><span>{{ money($order->tax_amount) }}</span></div>
                                    @endif
                                    <div class="d-flex justify-content-between border-top border-translucent pt-2 mt-2"><h5 class="mb-0">Total</h5><h5 class="mb-0">{{ money($order->total) }}</h5></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="{{ route('orders.track.form') }}" class="btn btn-phoenix-primary me-2">Track this order</a>
                        <a href="{{ route('products.index') }}" class="btn btn-phoenix-secondary">Continue shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
