@extends('admin.layouts.app')

@section('title', 'Return '.$return->reference.' — gobuy admin')
@section('page-title', 'Return '.$return->reference)

@section('content')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.returns.index') }}">Returns</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $return->reference }}</li>
        </ol>
    </nav>
    <x-admin.page-header :title="'Return '.$return->reference" :subtitle="'Order '.$return->order?->order_number">
        <x-slot:actions>
            <span class="badge badge-phoenix badge-phoenix-{{ $return->status->isSettled() ? 'success' : ($return->status->isOpen() ? 'warning' : 'secondary') }} fs-9">{{ $return->status->label() }}</span>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4"><div class="card-body">
                <h5 class="mb-3">Items</h5>
                <table class="table table-sm fs-9 mb-0">
                    <thead><tr><th>Item</th><th class="text-center">Qty</th><th>Condition</th><th>Resolution</th><th class="text-end">Paid/unit</th></tr></thead>
                    <tbody>
                        @foreach ($return->items as $item)
                            <tr>
                                <td class="text-body-emphasis">{{ $item->orderItem->name ?? 'Item' }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td>{{ ucfirst($item->condition_reported ?? '—') }}</td>
                                <td>
                                    @if ($item->resolution)
                                        <span class="badge badge-phoenix badge-phoenix-secondary">{{ $item->resolution->label() }}</span>
                                    @else
                                        <span class="text-body-tertiary">—</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ money($item->unit_price_snapshot) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($return->customer_note)
                    <div class="mt-3"><span class="fs-9 text-body-tertiary">Customer note:</span><p class="mb-0">{{ $return->customer_note }}</p></div>
                @endif

                @php($photos = $return->getMedia(\App\Modules\Returns\Models\ReturnRequest::MEDIA_PHOTOS))
                @if ($photos->isNotEmpty())
                    <div class="mt-3">
                        <span class="fs-9 text-body-tertiary d-block mb-2">Customer photos</span>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($photos as $photo)
                                @php($photoUrl = route('returns.photo', [$return, $photo]))
                                <a href="{{ $photoUrl }}" class="glightbox" data-gallery="return-photos">
                                    <img src="{{ $photoUrl }}" alt="Return photo" style="width:84px;height:84px;object-fit:cover;" class="rounded border">
                                </a>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- GLightbox -->
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
                    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const lightbox = GLightbox({
                                selector: '.glightbox',
                                touchNavigation: true,
                                loop: true,
                                zoomable: true
                            });
                        });
                    </script>
                @endif
            </div></div>

            <div class="card"><div class="card-body">
                <h5 class="mb-3">Actions</h5>
                <div class="d-flex flex-wrap gap-2">
                    @if (in_array($return->status->value, ['requested', 'info_requested'], true))
                        <form action="{{ route('admin.returns.approve', $return) }}" method="POST">@csrf
                            <button class="btn btn-primary btn-sm"><span class="fas fa-check me-2"></span>Approve</button>
                        </form>
                        <form action="{{ route('admin.returns.deny', $return) }}" method="POST">@csrf
                            <button class="btn btn-phoenix-danger btn-sm"><span class="fas fa-xmark me-2"></span>Deny</button>
                        </form>
                        <form action="{{ route('admin.returns.request-info', $return) }}" method="POST" class="w-100 mt-2">@csrf
                            <div class="input-group input-group-sm">
                                <input type="text" name="message" class="form-control" placeholder="Ask the customer for more info…" maxlength="500" required>
                                <button class="btn btn-phoenix-secondary"><span class="fas fa-circle-question me-1"></span>Request info</button>
                            </div>
                        </form>
                    @elseif (in_array($return->status->value, ['approved', 'awaiting_shipment', 'in_transit'], true))
                        <form action="{{ route('admin.returns.receive', $return) }}" method="POST">@csrf
                            <button class="btn btn-primary btn-sm"><span class="fas fa-box me-2"></span>Mark received</button>
                        </form>
                    @elseif (in_array($return->status->value, ['received', 'inspecting'], true))
                        <div class="w-100">
                            <form action="{{ route('admin.returns.inspect', $return) }}" method="POST" class="mb-3">
                                @csrf
                                <p class="fs-9 text-body-tertiary mb-2">Set a disposition and accepted quantity per item, then settle.</p>
                                @foreach ($return->items as $item)
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                        <span class="flex-grow-1 fs-9 text-body-emphasis">{{ $item->orderItem->name ?? 'Item' }} <span class="text-body-tertiary">(req {{ $item->quantity }})</span></span>
                                        <select name="items[{{ $item->id }}][disposition]" class="form-select form-select-sm w-auto">
                                            <option value="restock" @selected(($item->disposition?->value ?? 'restock') === 'restock')>Restock</option>
                                            <option value="damaged" @selected($item->disposition?->value === 'damaged')>Damaged</option>
                                            <option value="reject" @selected($item->disposition?->value === 'reject')>Reject</option>
                                        </select>
                                        <input type="number" name="items[{{ $item->id }}][approved_quantity]" class="form-control form-control-sm" style="width:80px" min="0" max="{{ $item->quantity }}" value="{{ $item->approved_quantity ?? $item->quantity }}">
                                    </div>
                                @endforeach
                                <button class="btn btn-phoenix-secondary btn-sm mt-1"><span class="fas fa-clipboard-check me-2"></span>Save inspection</button>
                            </form>
                            @can('manage_refunds')
                                <div class="d-inline-block">
                                    <button data-bs-toggle="modal" data-bs-target="#settleModal" type="button" class="btn btn-primary btn-sm">
                                        <span class="fas fa-money-bill-wave me-2"></span>Settle return
                                    </button>
                                    
                                    <!-- Settle Modal -->
                                    <div class="modal fade" id="settleModal" tabindex="-1" aria-labelledby="settleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content text-start">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="settleModalLabel">Settle Return Request</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="fs-9 text-body-secondary mb-3">You are about to finalize this return. This action cannot be undone.</p>
                                                    <ul class="fs-9 text-body-secondary mb-0">
                                                        <li><strong>Restock:</strong> Eligible items will be returned to inventory.</li>
                                                        <li><strong>Refund:</strong> The final calculated amount will be refunded to <strong>{{ $return->refund_destination->label() }}</strong>.</li>
                                                        <li><strong>Customer Notification:</strong> The customer will be emailed a final summary.</li>
                                                    </ul>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <form action="{{ route('admin.returns.settle', $return) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm"><span class="fas fa-check me-2"></span>Confirm & Settle</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @else
                                <p class="fs-9 text-body-tertiary mb-0">You don't have permission to settle refunds.</p>
                            @endcan
                        </div>
                    @else
                        <p class="text-body-tertiary mb-0 fs-9">No actions available in this status.</p>
                    @endif
                </div>
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4"><div class="card-body">
                <h6 class="mb-2">Summary</h6>
                <dl class="row fs-9 mb-0">
                    <dt class="col-5 text-body-tertiary fw-normal">Customer</dt><dd class="col-7">{{ $return->user?->name ?? $return->order?->customer_name }}</dd>
                    <dt class="col-5 text-body-tertiary fw-normal">Reason</dt><dd class="col-7">{{ ucfirst(str_replace('_', ' ', $return->reason_code)) }}</dd>
                    <dt class="col-5 text-body-tertiary fw-normal">Refund to</dt><dd class="col-7">{{ $return->refund_destination->label() }}</dd>
                    <dt class="col-5 text-body-tertiary fw-normal">Return shipping</dt><dd class="col-7">{{ ucfirst($return->return_shipping_payer) }}</dd>
                    @if ($return->returnShipment)
                        <dt class="col-5 text-body-tertiary fw-normal">Tracking</dt><dd class="col-7">{{ $return->returnShipment->tracking_reference }}</dd>
                    @endif
                </dl>
            </div></div>

            <div class="card mb-4 {{ ($return->risk_score ?? 0) >= 70 ? 'border-danger' : (($return->risk_score ?? 0) >= 40 ? 'border-warning' : '') }}"><div class="card-body">
                <h6 class="mb-2"><span class="fas fa-shield-halved me-2"></span>Risk assessment</h6>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge badge-phoenix badge-phoenix-{{ ($return->risk_score ?? 0) >= 70 ? 'danger' : (($return->risk_score ?? 0) >= 40 ? 'warning' : 'success') }} fs-9">Score {{ $return->risk_score ?? 0 }}/100</span>
                    @if ($return->auto_approved)<span class="badge badge-phoenix badge-phoenix-info fs-9">Auto-approved</span>@endif
                </div>
                @if (! empty($return->risk_flags))
                    <ul class="list-unstyled mb-0 fs-9">
                        @foreach ($return->risk_flags as $flag)
                            <li><span class="fas fa-flag text-warning me-2"></span>{{ ucfirst(str_replace('_', ' ', $flag)) }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="fs-9 text-body-tertiary mb-0">No risk signals flagged.</p>
                @endif
            </div></div>

            <div class="card"><div class="card-body">
                <h6 class="mb-3">Timeline</h6>
                <ul class="list-unstyled mb-0 fs-9">
                    @foreach ($return->events as $event)
                        <li class="d-flex gap-2 pb-2">
                            <span class="fas fa-circle text-primary fs-11 mt-1"></span>
                            <div>
                                <span class="fw-semibold text-body-emphasis d-block">{{ ucfirst(str_replace('_', ' ', $event->action)) }}</span>
                                <span class="text-body-tertiary fs-10">{{ $event->created_at->format('M j, g:i A') }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div></div>
        </div>
    </div>
@endsection
