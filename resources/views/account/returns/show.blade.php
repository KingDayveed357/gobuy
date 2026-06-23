@extends('layouts.storefront')

@section('title', 'Return '.$return->reference.' — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account.returns.index') }}">Returns</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $return->reference }}</li>
                </ol>
            </nav>

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Return {{ $return->reference }}</h2>
                    <p class="text-body-tertiary mb-0">Order {{ $return->order->order_number }} · Refund to {{ $return->refund_destination->label() }}</p>
                </div>
                <span class="badge badge-phoenix {{ $return->status->isSettled() ? 'badge-phoenix-success' : ($return->status->isOpen() ? 'badge-phoenix-warning' : 'badge-phoenix-secondary') }} fs-9">{{ $return->status->label() }}</span>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card mb-4"><div class="card-body">
                        <h5 class="mb-3">Items</h5>
                        <table class="table table-sm fs-9 mb-0">
                            <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Paid</th></tr></thead>
                            <tbody>
                                @foreach ($return->items as $item)
                                    <tr>
                                        <td class="text-body-emphasis">{{ $item->orderItem->name ?? 'Item' }}</td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">{{ money($item->unit_price_snapshot) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div></div>

                    @if ($return->status->value === 'info_requested')
                        @php($infoEvent = $return->events->firstWhere('action', 'info_requested'))
                        <div class="card mb-4 border-warning"><div class="card-body">
                            <h6 class="mb-2"><span class="fas fa-circle-question me-2"></span>We need a bit more info</h6>
                            @if ($infoEvent && data_get($infoEvent->meta, 'message'))
                                <p class="fs-9 mb-3">“{{ data_get($infoEvent->meta, 'message') }}”</p>
                            @endif
                            <form action="{{ route('account.returns.reply', $return) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <textarea name="message" class="form-control form-control-sm mb-2" rows="3" placeholder="Your reply…" maxlength="1000" required></textarea>
                                <input type="file" name="photos[]" class="form-control form-control-sm mb-2" accept="image/*" multiple>
                                <button class="btn btn-primary btn-sm"><span class="fas fa-paper-plane me-2"></span>Send reply</button>
                            </form>
                        </div></div>
                    @endif

                    @if (in_array($return->status->value, ['awaiting_shipment', 'in_transit'], true) && $return->returnShipment)
                        <div class="card mb-4 border-primary"><div class="card-body">
                            <h6 class="mb-2"><span class="fas fa-truck-fast me-2"></span>Ship your item back</h6>
                            <p class="fs-9 mb-1">Tracking: <span class="fw-semibold">{{ $return->returnShipment->tracking_reference }}</span>
                                <span class="badge {{ $return->returnShipment->isMerchantPaid() ? 'text-bg-success' : 'text-bg-secondary' }} ms-1">{{ $return->returnShipment->isMerchantPaid() ? 'Prepaid' : 'You pay shipping' }}</span>
                            </p>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a href="{{ route('account.returns.label', $return) }}" class="btn btn-phoenix-primary btn-sm"><span class="fas fa-tag me-2"></span>View / print label</a>
                                @if ($return->status->value === 'awaiting_shipment')
                                    <form action="{{ route('account.returns.shipped', $return) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm"><span class="fas fa-check me-2"></span>I've shipped it</button>
                                    </form>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-info align-self-center">On its way back</span>
                                @endif
                            </div>
                        </div></div>
                    @endif

                    @if ($return->status->isOpen())
                        <form action="{{ route('account.returns.cancel', $return) }}" method="POST" onsubmit="return confirm('Cancel this return request?');">
                            @csrf
                            <button type="submit" class="btn btn-phoenix-danger btn-sm">Cancel return</button>
                        </form>
                    @endif
                </div>

                <div class="col-lg-5">
                    <div class="card"><div class="card-body">
                        <h5 class="mb-3">Timeline</h5>
                        <ul class="list-unstyled mb-0">
                            @foreach ($return->events as $event)
                                <li class="d-flex gap-3 pb-3">
                                    <span class="fas fa-circle text-primary fs-11 mt-1"></span>
                                    <div>
                                        <p class="mb-0 fw-semibold text-body-emphasis fs-9">{{ ucfirst(str_replace('_', ' ', $event->action)) }}@if ($event->to_status) → {{ \App\Modules\Returns\Enums\ReturnStatus::from($event->to_status)->label() }}@endif</p>
                                        <span class="fs-10 text-body-tertiary">{{ $event->created_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div></div>
                </div>
            </div>
        </div>
    </section>
@endsection
