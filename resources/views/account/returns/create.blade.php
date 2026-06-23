@extends('layouts.storefront')

@section('title', 'Request a return — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account.orders') }}">Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Request return</li>
                </ol>
            </nav>

            <h2 class="mb-1">Request a return</h2>
            <p class="text-body-tertiary mb-4">
                Order {{ $order->order_number }} ·
                @if ($windowExpiresAt)
                    Return window closes {{ $windowExpiresAt->format('M j, Y') }}
                @endif
            </p>

            @if (session('error'))
                <div class="alert alert-subtle-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('account.returns.store', $order) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', \Illuminate\Support\Str::uuid()) }}">

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Which items are you returning?</h5>
                        @foreach ($eligibleItems as $item)
                            <div class="d-flex flex-wrap align-items-center gap-3 border-bottom border-translucent py-3">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="sel-{{ $item->id }}" name="items[{{ $item->id }}][selected]" value="1" @checked(old("items.{$item->id}.selected"))>
                                    <input type="hidden" name="items[{{ $item->id }}][order_item_id]" value="{{ $item->id }}">
                                </div>
                                <label class="flex-grow-1 mb-0" for="sel-{{ $item->id }}">
                                    <span class="fw-semibold text-body-emphasis d-block">{{ $item->name }}</span>
                                    <span class="fs-9 text-body-tertiary">Purchased {{ $item->quantity }} · {{ money($item->unit_price) }} each</span>
                                </label>
                                <div style="width: 90px;">
                                    <label class="form-label fs-9 mb-1">Qty</label>
                                    <input type="number" class="form-control form-control-sm" name="items[{{ $item->id }}][quantity]" min="1" max="{{ $item->returnableQuantity() }}" value="{{ old("items.{$item->id}.quantity", 1) }}">
                                </div>
                                <div style="width: 150px;">
                                    <label class="form-label fs-9 mb-1">Condition</label>
                                    <select class="form-select form-select-sm" name="items[{{ $item->id }}][condition_reported]">
                                        <option value="unopened">Unopened</option>
                                        <option value="opened">Opened</option>
                                        <option value="damaged">Damaged</option>
                                    </select>
                                </div>
                            </div>
                        @endforeach

                        @if (! empty($blocked))
                            <p class="fs-9 text-body-tertiary mt-3 mb-0">
                                Some items can't be returned:
                                @foreach ($blocked as $b) <span class="d-block">• {{ $b['item']->name }} — {{ $b['reason'] }}</span> @endforeach
                            </p>
                        @endif
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Reason & refund</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="reason_code">Reason for return</label>
                                <select class="form-select" id="reason_code" name="reason_code" required>
                                    @foreach ($reasons as $reason)
                                        <option value="{{ $reason->value }}" @selected(old('reason_code') === $reason->value)>{{ $reason->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="refund_destination">Refund to</label>
                                <select class="form-select" id="refund_destination" name="refund_destination" required>
                                    @foreach ($destinations as $destination)
                                        <option value="{{ $destination->value }}" @selected(old('refund_destination', 'store_credit') === $destination->value)>{{ $destination->label() }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Store credit is instant. Refunds to your bank take 3–7 business days.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="photos">Photos <span class="text-body-tertiary fw-normal">(required for damaged / faulty / wrong items)</span></label>
                                <input type="file" class="form-control @error('photos') is-invalid @enderror @error('photos.0') is-invalid @enderror" id="photos" name="photos[]" accept="image/*" multiple>
                                <div class="form-text">Up to 5 images, 5MB each. Clear photos help us approve your return faster.</div>
                                @error('photos')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="customer_note">Anything else? <span class="text-body-tertiary fw-normal">(optional)</span></label>
                                <textarea class="form-control" id="customer_note" name="customer_note" rows="3" maxlength="1000">{{ old('customer_note') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4"><span class="fas fa-rotate-left me-2"></span>Submit return request</button>
                <a href="{{ route('account.orders') }}" class="btn btn-phoenix-secondary ms-2">Cancel</a>
            </form>
        </div>
    </section>
@endsection
