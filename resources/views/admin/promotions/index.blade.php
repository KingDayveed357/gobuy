@extends('admin.layouts.app')

@section('title', 'Promotions — Quintessential Mart admin')
@section('page-title', 'Promotions')

@section('content')
    <x-admin.page-header title="Promotions" subtitle="Scheduled, time-bound price overlays — they win over a product's sale price while live.">
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">Schedule a promotion</h4>
                    <form action="{{ route('admin.promotions.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="product_id">Product</label>
                            <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                                <option value="">Select a product…</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }}</option>
                                @endforeach
                            </select>
                            @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="label">Campaign label <span class="text-body-tertiary fw-normal">(optional)</span></label>
                            <input type="text" class="form-control" id="label" name="label" value="{{ old('label') }}" placeholder="e.g. Sallah Sale" maxlength="120">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-5">
                                <label class="form-label" for="discount_type">Type</label>
                                <select class="form-select" id="discount_type" name="discount_type">
                                    <option value="percentage" @selected(old('discount_type') === 'percentage')>% off</option>
                                    <option value="fixed" @selected(old('discount_type') === 'fixed')>Flat ₦</option>
                                </select>
                            </div>
                            <div class="col-7">
                                <label class="form-label" for="value">Value</label>
                                <input type="number" step="0.01" min="0.01" class="form-control @error('value') is-invalid @enderror" id="value" name="value" value="{{ old('value') }}" required>
                                @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <p class="fs-9 text-body-tertiary mb-3">% off computes a price per variant from each variant's retail. Flat ₦ sets the same promo price on every variant.</p>

                        <div class="mb-3">
                            <label class="form-label" for="starts_at">Starts <span class="text-body-tertiary fw-normal">(optional)</span></label>
                            <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror" id="starts_at" name="starts_at" value="{{ old('starts_at') }}">
                            @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="ends_at">Ends <span class="text-body-tertiary fw-normal">(optional)</span></label>
                            <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror" id="ends_at" name="ends_at" value="{{ old('ends_at') }}">
                            @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><span class="fas fa-zap me-2"></span>Schedule promotion</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <x-admin.table
                :cols="[
                    ['label' => 'Product'],
                    ['label' => 'Promo price', 'align' => 'end'],
                    ['label' => 'Window'],
                    ['label' => 'Status'],
                    ['label' => '', 'align' => 'end'],
                ]"
                :empty="$promotions->isEmpty()"
                empty-icon="fa-bolt"
                empty-text="No promotions scheduled yet."
            >
                @foreach ($promotions as $row)
                    <tr>
                        <td class="fw-semibold text-body-emphasis">
                            {{ $row['product']->name }}
                            @if ($row['label'])<span class="d-block fs-9 text-body-tertiary fw-normal">{{ $row['label'] }}</span>@endif
                        </td>
                        <td class="text-end">
                            {{ money($row['from']) }}@if ($row['to'] !== $row['from']) – {{ money($row['to']) }}@endif
                        </td>
                        <td class="fs-9">
                            @if ($row['starts_at'] || $row['ends_at'])
                                {{ $row['starts_at']?->format('d M Y, H:i') ?? 'Now' }} → {{ $row['ends_at']?->format('d M Y, H:i') ?? 'Open' }}
                            @else
                                <span class="text-body-tertiary">Always on</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-phoenix badge-phoenix-{{ $row['live'] ? 'success' : 'warning' }}">
                                {{ $row['live'] ? 'Live' : 'Scheduled' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-phoenix-danger" title="End promotion" data-bs-toggle="modal" data-bs-target="#actionModal" data-action="{{ route('admin.promotions.destroy', $row['product']) }}" data-method="DELETE" data-title="End Promotion" data-message="Are you sure you want to end this promotion?" data-confirm-text="Yes, end it" data-variant="danger"><span class="fas fa-trash"></span></button>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </div>
    </div>
@endsection
