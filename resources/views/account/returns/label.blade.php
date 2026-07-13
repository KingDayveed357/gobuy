@extends('layouts.storefront')

@section('title', 'Return label '.$shipment->tracking_reference.' — Quintessential Mart')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small" style="max-width: 640px;">
            <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
                <a href="{{ route('account.returns.show', $return) }}" class="btn btn-phoenix-secondary btn-sm">← Back</a>
                <button onclick="window.print()" class="btn btn-primary btn-sm"><span class="fas fa-print me-2"></span>Print label</button>
            </div>

            <div class="card border border-2 border-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom border-dark pb-3 mb-3">
                        <div>
                            <h4 class="mb-0">{{ $shipment->carrier }}</h4>
                            <span class="fs-9 text-body-tertiary">Return label</span>
                        </div>
                        <div class="text-end">
                            <span class="badge {{ $shipment->isMerchantPaid() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $shipment->isMerchantPaid() ? 'PREPAID' : 'Sender pays' }}</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <p class="fs-9 text-body-tertiary mb-1">RMA</p>
                            <p class="fw-bold mb-0">{{ $return->reference }}</p>
                        </div>
                        <div class="col-6 text-end">
                            <p class="fs-9 text-body-tertiary mb-1">Tracking</p>
                            <p class="fw-bold mb-0">{{ $shipment->tracking_reference }}</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <p class="fs-9 text-body-tertiary mb-1">Ship to</p>
                        <p class="fw-semibold mb-0">{{ $dropoffAddress }}</p>
                    </div>

                    <div class="text-center border-top border-dark pt-3">
                        <div style="font-family: monospace; letter-spacing: 4px; font-size: 1.4rem;">*{{ $shipment->tracking_reference }}*</div>
                        <p class="fs-10 text-body-tertiary mt-2 mb-0">Original order {{ $return->order->order_number }}</p>
                    </div>
                </div>
            </div>

            <p class="fs-9 text-body-tertiary mt-3 d-print-none">
                Pack the item securely, attach this label, and drop it off or hand it to the carrier.
                @unless ($shipment->isMerchantPaid()) Return shipping is paid by you for this reason. @endunless
            </p>
        </div>
    </section>
@endsection
