@extends('admin.layouts.app')

@section('title', 'Bulk pricing — gobuy admin')
@section('page-title', 'Bulk pricing')

@php($input = $input ?? [])

@section('content')
    <x-admin.page-header title="Bulk pricing" subtitle="Adjust a price field across a category or the whole catalog. Every change is written to price history.">
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">Adjustment</h4>
                    {{-- Preview first; the result panel offers a confirm button. --}}
                    <form action="{{ route('admin.pricing.bulk.preview') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="category_id">Category</label>
                            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                                <option value="">Entire catalog</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(($input['category_id'] ?? null) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="field">Price field</label>
                            <select class="form-select" id="field" name="field">
                                <option value="retail_price" @selected(($input['field'] ?? 'retail_price') === 'retail_price')>Retail price</option>
                                <option value="sale_price" @selected(($input['field'] ?? '') === 'sale_price')>Sale price</option>
                                <option value="wholesale_price" @selected(($input['field'] ?? '') === 'wholesale_price')>Wholesale price</option>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label" for="direction">Direction</label>
                                <select class="form-select" id="direction" name="direction">
                                    <option value="increase" @selected(($input['direction'] ?? 'increase') === 'increase')>Increase</option>
                                    <option value="decrease" @selected(($input['direction'] ?? '') === 'decrease')>Decrease</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="method">By</label>
                                <select class="form-select" id="method" name="method">
                                    <option value="percentage" @selected(($input['method'] ?? 'percentage') === 'percentage')>Percentage</option>
                                    <option value="fixed" @selected(($input['method'] ?? '') === 'fixed')>Fixed ₦</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="value">Value</label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('value') is-invalid @enderror" id="value" name="value" value="{{ $input['value'] ?? old('value') }}" required>
                            @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="reason">Reason <span class="text-body-tertiary fw-normal">(optional)</span></label>
                            <input type="text" class="form-control" id="reason" name="reason" value="{{ $input['reason'] ?? old('reason') }}" placeholder="e.g. Supplier cost increase" maxlength="160">
                        </div>

                        <button type="submit" class="btn btn-phoenix-primary w-100"><span class="fas fa-magnifying-glass me-2"></span>Preview changes</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            @if ($preview === null)
                <div class="card h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center text-body-tertiary py-6">
                        <span class="fas fa-scale-balanced fs-3 mb-3"></span>
                        <p class="mb-0">Preview an adjustment to see exactly which variants change and by how much before you commit.</p>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Preview <span class="text-body-tertiary fw-normal fs-9">({{ $preview['count'] }} variant(s) affected)</span></h4>
                            @if ($preview['count'] > 0)
                                <form id="bulkForm" action="{{ route('admin.pricing.bulk.store') }}" method="POST">
                                    @csrf
                                    @foreach (['category_id', 'field', 'direction', 'method', 'value', 'reason'] as $field)
                                        <input type="hidden" name="{{ $field }}" value="{{ $input[$field] ?? '' }}">
                                    @endforeach
                                    <button type="button" class="btn btn-sm btn-phoenix-primary" data-bs-toggle="modal" data-bs-target="#actionModal" data-form-id="bulkForm" data-title="Apply Bulk Pricing" data-message="Apply this price change to {{ $preview['count'] }} variant(s)? This is recorded in price history." data-confirm-text="Yes, apply changes" data-variant="primary"><span class="fas fa-check me-2"></span>Apply Changes</button>
                                </form>
                            @endif
                        </div>

                        @if ($preview['count'] === 0)
                            <p class="text-body-tertiary mb-0">No variants would change with these settings.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm fs-9 mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-end">Current</th>
                                            <th class="text-end">New</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($preview['rows']->take(100) as $row)
                                            <tr>
                                                <td class="text-body-emphasis">{{ $row['product'] }} <span class="text-body-tertiary">{{ $row['label'] }}</span></td>
                                                <td class="text-end text-body-tertiary text-decoration-line-through">{{ money($row['old']) }}</td>
                                                <td class="text-end fw-semibold">{{ money($row['new']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if ($preview['count'] > 100)
                                    <p class="fs-9 text-body-tertiary mt-2 mb-0">Showing first 100 of {{ $preview['count'] }}.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
