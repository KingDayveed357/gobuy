@extends('layouts.storefront')

@section('title', 'Order '.$order->order_number.' status — gobuy')

@php
    use App\Modules\Order\Enums\OrderStatus;

    $history = $order->statusHistories->keyBy(fn ($h) => $h->status->value);

    $steps = [
        OrderStatus::Pending->value => ['Order placed', 'We received your order.', 'fa-receipt'],
        OrderStatus::Paid->value => ['Payment confirmed', 'Your payment has been received.', 'fa-credit-card'],
        OrderStatus::Processing->value => ['Processing', 'Your order is being prepared.', 'fa-box'],
        OrderStatus::Shipped->value => ['Shipped', 'Your order is on the way.', 'fa-truck-ramp-box'],
        OrderStatus::Delivered->value => ['Delivered', 'Your order has been delivered.', 'fa-house'],
        OrderStatus::Completed->value => ['Completed', 'Order complete. Enjoy!', 'fa-circle-check'],
    ];
@endphp

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small cart">
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-5">
                <div>
                    <h2 class="mb-1">Order {{ $order->order_number }} status</h2>
                    <p class="text-body-secondary mb-0">
                        Placed {{ $order->placed_at?->format('M j, Y g:i A') }} ·
                        <span class="fw-bold">{{ $order->status->label() }}</span>
                    </p>
                </div>
                <a href="{{ route('orders.success', $order) }}" class="btn btn-outline-primary mt-3">
                    <span class="fas fa-receipt me-2"></span>View receipt
                </a>
            </div>

            @if ($order->status === OrderStatus::Cancelled)
                <div class="alert alert-subtle-danger">This order was cancelled.</div>
            @endif

            <div class="row gy-5">
                <div class="col-12 col-lg-7">
                    <div class="timeline-vertical">
                        @foreach ($steps as $statusValue => [$title, $desc, $icon])
                            @php($entry = $history->get($statusValue))
                            @php($reached = $entry !== null)
                            <div class="timeline-item">
                                <div class="row g-md-3 align-items-center mb-5">
                                    <div class="col-12 col-md-auto d-flex">
                                        <div class="timeline-item-date text-end order-1 order-md-0 me-md-4" style="min-width: 110px;">
                                            <p class="fs-10 fw-semibold text-body-tertiary mb-0">
                                                {{ $reached ? $entry->created_at->format('M j, Y g:i A') : 'Pending' }}
                                            </p>
                                        </div>
                                        <div class="timeline-item-bar position-relative me-3 me-md-0">
                                            <div class="icon-item icon-item-sm {{ $reached ? 'bg-success' : 'bg-body-quaternary' }}" data-bs-theme="light">
                                                <span class="fa-solid {{ $reached ? 'fa-check' : $icon }} text-white fs-10"></span>
                                            </div>
                                            <span class="timeline-bar border-end {{ $reached ? 'border-success' : 'border-dashed' }}"></span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="timeline-item-content ps-6 ps-md-3">
                                            <h4 class="{{ $reached ? '' : 'text-body-tertiary' }}">{{ $title }}</h4>
                                            <p class="fs-9 text-body-secondary mb-0">{{ $desc }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-3">Order summary</h4>
                            @foreach ($order->items as $item)
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fs-9 text-body line-clamp-1 me-2">{{ $item->name }} <span class="text-body-tertiary">x{{ $item->quantity }}</span></span>
                                    <span class="fs-9 fw-semibold text-nowrap">₦{{ number_format($item->line_total, 2) }}</span>
                                </div>
                            @endforeach
                            <div class="d-flex justify-content-between border-top border-translucent pt-2 mt-2">
                                <h5 class="mb-0">Total</h5>
                                <h5 class="mb-0">₦{{ number_format($order->total, 2) }}</h5>
                            </div>
                            <hr class="border-translucent">
                            <h6 class="mb-2">Delivery to</h6>
                            <p class="fs-9 text-body-secondary mb-0">
                                {{ $order->customer_name }}<br>
                                {{ $order->address_line }}, {{ $order->city }}, {{ $order->state }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
