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
                                                <td class="text-end">₦{{ number_format($item->unit_price, 2) }}</td>
                                                <td class="text-end fw-semibold">₦{{ number_format($item->line_total, 2) }}</td>
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
                                    <div class="d-flex justify-content-between"><span class="text-body-tertiary">Subtotal</span><span>₦{{ number_format($order->subtotal, 2) }}</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-body-tertiary">Delivery</span><span>₦{{ number_format($order->delivery_fee, 2) }}</span></div>
                                    <div class="d-flex justify-content-between border-top border-translucent pt-2 mt-2"><h5 class="mb-0">Total</h5><h5 class="mb-0">₦{{ number_format($order->total, 2) }}</h5></div>
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
