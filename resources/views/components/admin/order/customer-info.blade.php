@props(['order'])

<div class="card mb-4">
    <div class="card-body">
        @if($order->hasSeparateAddresses())
            <div class="row gx-4 gy-6 g-xl-7 justify-content-sm-center justify-content-xl-start">
                <div class="col-12 col-sm-auto">
                    {{-- Placeholder for when addresses are split --}}
                </div>
            </div>
        @else
            <h3 class="card-title mb-4">Customer Details</h3>
            <div class="row align-items-start">
                <div class="col-12 col-md-6 mb-3 mb-md-0">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-item icon-item-sm bg-primary-subtle text-primary me-3">
                            <span class="fas fa-user fs-10"></span>
                        </div>
                        <div>
                            <h6 class="mb-0 text-body-tertiary">Name</h6>
                            <p class="mb-0 fs-9 fw-semibold text-body-emphasis">
                                {{ $order->customer_name }}
                                @if ($order->customer_type === \App\Models\User::TYPE_WHOLESALE)
                                    <span class="badge badge-phoenix badge-phoenix-success ms-2">Wholesale</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="icon-item icon-item-sm bg-info-subtle text-info me-3">
                            <span class="fas fa-envelope fs-10"></span>
                        </div>
                        <div>
                            <h6 class="mb-0 text-body-tertiary">Email</h6>
                            <a href="mailto:{{ $order->customer_email }}" class="mb-0 fs-9 fw-semibold text-body-emphasis">{{ $order->customer_email }}</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-item icon-item-sm bg-success-subtle text-success me-3">
                            <span class="fas fa-phone fs-10"></span>
                        </div>
                        <div>
                            <h6 class="mb-0 text-body-tertiary">Phone</h6>
                            <a href="tel:{{ $order->customer_phone }}" class="mb-0 fs-9 fw-semibold text-body-emphasis">{{ $order->customer_phone }}</a>
                        </div>
                    </div>
                    <div class="d-flex align-items-start">
                        <div class="icon-item icon-item-sm bg-warning-subtle text-warning me-3">
                            <span class="fas fa-location-dot fs-10"></span>
                        </div>
                        <div>
                            <h6 class="mb-0 text-body-tertiary">Address</h6>
                            <p class="mb-0 fs-9 fw-semibold text-body-emphasis">
                                {{ $order->address_line }}<br>
                                {{ $order->city }}, {{ $order->state }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
