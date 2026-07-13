@extends('layouts.storefront')

@section('title', 'Order '.$order->order_number.' status — Quintessential Mart')

@php
    use App\Modules\Order\Enums\OrderStatus;
    use App\Modules\Order\Enums\PaymentMethod;
    use App\Modules\Order\Enums\PaymentStatus;

    $history = $order->statusHistories->keyBy(fn ($h) => $h->status->value);
    $ps = $order->payment_status;
    $isCancelled = $order->status === OrderStatus::Cancelled;
    $paymentFailed = $ps === PaymentStatus::Failed;

    // "Accepted" = the order reached Paid at some point (online payment confirmed,
    // or a POD/bank order accepted). Keyed off the status history rather than the
    // current payment_status, so a POD order (accepted but still unpaid) reads as
    // confirmed instead of "awaiting payment".
    $acceptedStatuses = [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Completed, OrderStatus::Refunded];
    $wasAccepted = $history->has(OrderStatus::Paid->value) || in_array($order->status, $acceptedStatuses, true);

    // Build a timeline that reflects the REAL payment + order state rather than
    // always walking the fulfilment steps (which made a failed/cancelled order
    // look like it was merely "pending").
    $timeline = [];

    $timeline[] = ['title' => 'Order placed', 'desc' => 'We received your order.', 'icon' => 'fa-receipt', 'state' => 'done', 'date' => $order->placed_at];

    if ($wasAccepted) {
        $podPending = $order->payment_method === PaymentMethod::PayOnDelivery && $ps !== PaymentStatus::Paid;
        $timeline[] = ['title' => $podPending ? 'Order confirmed' : 'Payment confirmed', 'desc' => $podPending ? 'Confirmed — you will pay on delivery.' : 'Your payment has been received.', 'icon' => 'fa-credit-card', 'state' => 'done', 'date' => $history->get(OrderStatus::Paid->value)?->created_at];
    } elseif ($paymentFailed) {
        $timeline[] = ['title' => 'Payment failed', 'desc' => 'We could not confirm your payment, so the order was not processed.', 'icon' => 'fa-circle-xmark', 'state' => 'failed', 'date' => null];
    } else {
        $timeline[] = ['title' => 'Awaiting payment', 'desc' => $order->payment_method === PaymentMethod::PayOnDelivery ? 'You will pay on delivery.' : 'Complete your payment to continue.', 'icon' => 'fa-credit-card', 'state' => 'pending', 'date' => null];
    }

    if ($isCancelled) {
        $timeline[] = ['title' => 'Order cancelled', 'desc' => $paymentFailed ? 'This order was cancelled because the payment was not completed.' : 'This order was cancelled.', 'icon' => 'fa-ban', 'state' => 'cancelled', 'date' => $history->get(OrderStatus::Cancelled->value)?->created_at];
    } else {
        $fulfilment = [
            OrderStatus::Processing->value => ['Processing', 'Your order is being prepared.', 'fa-box'],
            OrderStatus::Shipped->value => ['Shipped', 'Your order is on the way.', 'fa-truck-ramp-box'],
            OrderStatus::Delivered->value => ['Delivered', 'Your order has been delivered.', 'fa-house'],
            OrderStatus::Completed->value => ['Completed', 'Order complete. Enjoy!', 'fa-circle-check'],
        ];
        foreach ($fulfilment as $statusValue => [$title, $desc, $icon]) {
            $entry = $history->get($statusValue);
            $timeline[] = ['title' => $title, 'desc' => $desc, 'icon' => $icon, 'state' => $entry ? 'done' : 'pending', 'date' => $entry?->created_at];
        }
        if ($order->status === OrderStatus::Refunded) {
            $timeline[] = ['title' => 'Refunded', 'desc' => 'Your payment was refunded.', 'icon' => 'fa-rotate-left', 'state' => 'failed', 'date' => $history->get(OrderStatus::Refunded->value)?->created_at];
        }
    }
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

            @if ($isCancelled)
                <div class="alert alert-subtle-danger d-flex align-items-center gap-2">
                    <span class="fas fa-ban"></span>
                    <span>{{ $paymentFailed ? 'This order was cancelled because the payment was not completed.' : 'This order was cancelled.' }}</span>
                </div>
            @elseif ($paymentFailed)
                <div class="alert alert-subtle-warning d-flex align-items-center gap-2">
                    <span class="fas fa-triangle-exclamation"></span>
                    <span>Your payment could not be confirmed. Please place the order again to retry.</span>
                </div>
            @endif

            <div class="row gy-5">
                <div class="col-12 col-lg-7">
                    <div class="timeline-vertical">
                        @foreach ($timeline as $step)
                            @php($state = $step['state'])
                            @php($iconBg = $state === 'done' ? 'bg-success' : ($state === 'pending' ? 'bg-body-quaternary' : 'bg-danger'))
                            @php($barClass = $state === 'done' ? 'border-success' : ($state === 'pending' ? 'border-dashed' : 'border-danger'))
                            @php($isBad = in_array($state, ['failed', 'cancelled'], true))
                            <div class="timeline-item">
                                <div class="row g-md-3 align-items-center mb-5">
                                    <div class="col-12 col-md-auto d-flex">
                                        <div class="timeline-item-date text-end order-1 order-md-0 me-md-4" style="min-width: 110px;">
                                            <p class="fs-10 fw-semibold text-body-tertiary mb-0">
                                                {{ $step['date']?->format('M j, Y g:i A') ?? ($state === 'pending' ? 'Pending' : '—') }}
                                            </p>
                                        </div>
                                        <div class="timeline-item-bar position-relative me-3 me-md-0">
                                            <div class="icon-item icon-item-sm {{ $iconBg }}" data-bs-theme="light">
                                                <span class="fa-solid {{ $state === 'done' ? 'fa-check' : $step['icon'] }} text-white fs-10"></span>
                                            </div>
                                            <span class="timeline-bar border-end {{ $barClass }}"></span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="timeline-item-content ps-6 ps-md-3">
                                            <h4 class="{{ $isBad ? 'text-danger' : ($state === 'pending' ? 'text-body-tertiary' : '') }}">{{ $step['title'] }}</h4>
                                            <p class="fs-9 text-body-secondary mb-0">{{ $step['desc'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    @if ($order->shipment && ! $isCancelled)
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex flex-between-center mb-3">
                                    <h4 class="mb-0">Delivery</h4>
                                    <span class="badge badge-phoenix badge-phoenix-info">{{ $order->shipment->methodLabel() }}</span>
                                </div>
                                @if ($order->shipment->isPickup() && $order->shipment->pickupLocation)
                                    <p class="fs-9 text-body-secondary mb-3">Pick up from <span class="fw-semibold">{{ $order->shipment->pickupLocation->name }}</span> — {{ $order->shipment->pickupLocation->formatted() }}@if ($order->shipment->pickupLocation->opening_hours) ({{ $order->shipment->pickupLocation->opening_hours }})@endif</p>
                                @endif
                                <x-shipment-timeline :shipment="$order->shipment" />
                            </div>
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-3">Order summary</h4>
                            @foreach ($order->items as $item)
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fs-9 text-body line-clamp-1 me-2">{{ $item->name }} <span class="text-body-tertiary">x{{ $item->quantity }}</span></span>
                                    <span class="fs-9 fw-semibold text-nowrap">{{ money($item->line_total) }}</span>
                                </div>
                            @endforeach
                            <div class="d-flex justify-content-between border-top border-translucent pt-2 mt-2">
                                <h5 class="mb-0">Total</h5>
                                <h5 class="mb-0">{{ money($order->total) }}</h5>
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
