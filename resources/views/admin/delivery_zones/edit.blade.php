@extends('admin.layouts.app')

@section('title', 'Edit Delivery Zone — gobuy admin')
@section('page-title', 'Edit Delivery Zone')

@section('content')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.delivery-zones.index') }}">Delivery Zones</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Zone</li>
        </ol>
    </nav>
    <x-admin.page-header title="Edit Delivery Zone" />

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.delivery-zones.update', $deliveryZone) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Zone Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $deliveryZone->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label" for="states">Covered States <span class="fw-normal text-body-tertiary">(comma separated)</span></label>
                        <input type="text" class="form-control @error('states') is-invalid @enderror" id="states" name="states" value="{{ old('states', $deliveryZone->states->pluck('state')->implode(', ')) }}" placeholder="Lagos, Abuja, Rivers">
                        <div class="form-text">If a state is mapped to another zone, it will be moved here.</div>
                        @error('states')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label" for="base_fee">Base Fee (₦)</label>
                        <input type="number" class="form-control @error('base_fee') is-invalid @enderror" id="base_fee" name="base_fee" value="{{ old('base_fee', optional($deliveryZone->base_fee)->naira) }}" min="0" step="0.01" required>
                        @error('base_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="per_kg_fee">Per Kg Fee (₦)</label>
                        <input type="number" class="form-control @error('per_kg_fee') is-invalid @enderror" id="per_kg_fee" name="per_kg_fee" value="{{ old('per_kg_fee', optional($deliveryZone->per_kg_fee)->naira) }}" min="0" step="0.01" required>
                        @error('per_kg_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="free_over_subtotal">Free Shipping Threshold (₦) <span class="fw-normal text-body-tertiary">(optional)</span></label>
                        <input type="number" class="form-control @error('free_over_subtotal') is-invalid @enderror" id="free_over_subtotal" name="free_over_subtotal" value="{{ old('free_over_subtotal', optional($deliveryZone->free_over_subtotal)->naira) }}" min="0" step="0.01">
                        <div class="form-text">Leave blank if no free shipping.</div>
                        @error('free_over_subtotal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="sort_order">Sort Order</label>
                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $deliveryZone->sort_order) }}" min="0" required>
                        <div class="form-text">Lower numbers are evaluated first as fallback if no state matches.</div>
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 d-flex align-items-center mt-md-5">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $deliveryZone->is_active))>
                            <label class="form-check-label" for="is_active">Zone is Active</label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.delivery-zones.index') }}" class="btn btn-phoenix-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary"><span class="fas fa-save me-2"></span>Update Zone</button>
                </div>
            </form>
        </div>
    </div>
@endsection
