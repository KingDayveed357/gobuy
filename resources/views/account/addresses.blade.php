@extends('layouts.account')

@section('title', 'My Addresses — gobuy')

@php
    $pageTitle = 'Addresses';
@endphp

@section('account_content')
    <div class="d-flex flex-between-center mb-4">
        <div>
            <h3 class="mb-1 text-body-emphasis">My Addresses</h3>
            <p class="text-body-tertiary mb-0">Saved addresses speed up your checkout.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addAddressCollapse" aria-expanded="false" aria-controls="addAddressCollapse">
            <span class="fas fa-plus me-2"></span>Add Address
        </button>
    </div>

    @if ($errors->any())
        <div class="alert alert-subtle-danger shadow-sm border-0 mb-4">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="collapse {{ $errors->any() && ! old('_edit') ? 'show' : '' }} mb-4" id="addAddressCollapse">
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">New Address</h5>
                <button type="button" class="btn-close fs-10" data-bs-toggle="collapse" data-bs-target="#addAddressCollapse" aria-label="Close"></button>
            </div>
            @include('account._address-form', ['action' => route('account.addresses.store')])
        </div></div>
    </div>

    @if ($addresses->isEmpty())
        <div class="card border-0 shadow-sm"><div class="card-body text-center py-6">
            <span class="fas fa-map-marker-alt fs-1 text-body-tertiary mb-3"></span>
            <h5 class="mb-2 text-body-emphasis">No addresses yet</h5>
            <p class="text-body-tertiary mb-4">Add an address to check out faster.</p>
            <button class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#addAddressCollapse">Add your first address</button>
        </div></div>
    @else
        <div class="row g-3">
            @foreach ($addresses as $address)
                <div class="col-md-6 col-xl-6">
                    <div class="card h-100 border-0 shadow-sm hover-actions-trigger transition-base">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex flex-between-center mb-3">
                                <h6 class="mb-0 text-body-emphasis"><span class="fas fa-home me-2 text-body-tertiary"></span>{{ $address->label ?: 'Address' }}</h6>
                                <div class="d-flex gap-1 flex-wrap justify-content-end">
                                    @if ($address->is_default_shipping)<span class="badge badge-phoenix badge-phoenix-success rounded-pill fs-10">Shipping</span>@endif
                                    @if ($address->is_default_billing)<span class="badge badge-phoenix badge-phoenix-info rounded-pill fs-10">Billing</span>@endif
                                </div>
                            </div>
                            
                            <div class="flex-1 mb-4">
                                <p class="fs-9 mb-1 fw-bold text-body-emphasis">{{ $address->recipient_name }} <span class="text-body-tertiary fw-normal ms-2">{{ $address->phone }}</span></p>
                                <p class="fs-9 text-body-secondary mb-0 lh-sm">{{ $address->formatted() }}</p>
                            </div>

                            <div class="d-flex flex-wrap gap-2 pt-3 border-top border-translucent">
                                <a class="btn btn-sm btn-phoenix-secondary flex-1" data-bs-toggle="collapse" href="#edit-{{ $address->id }}"><span class="fas fa-edit me-1"></span>Edit</a>
                                
                                <div class="dropdown flex-1">
                                    <button class="btn btn-sm btn-phoenix-secondary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        More
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        @unless ($address->is_default_shipping)
                                            <li>
                                                <form action="{{ route('account.addresses.default', $address) }}" method="POST">
                                                    @csrf<input type="hidden" name="purpose" value="shipping">
                                                    <button class="dropdown-item" type="submit">Set as default shipping</button>
                                                </form>
                                            </li>
                                        @endunless
                                        @unless ($address->is_default_billing)
                                            <li>
                                                <form action="{{ route('account.addresses.default', $address) }}" method="POST">
                                                    @csrf<input type="hidden" name="purpose" value="billing">
                                                    <button class="dropdown-item" type="submit">Set as default billing</button>
                                                </form>
                                            </li>
                                        @endunless
                                        @if(!$address->is_default_shipping && !$address->is_default_billing)
                                            <li><hr class="dropdown-divider"></li>
                                        @endif
                                        <li>
                                            <form action="{{ route('account.addresses.destroy', $address) }}" method="POST" onsubmit="return confirm('Remove this address?');">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger" type="submit">Delete address</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="collapse mt-3" id="edit-{{ $address->id }}">
                                <div class="p-3 bg-body-highlight rounded-3 border border-translucent">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">Edit Address</h6>
                                        <button type="button" class="btn-close fs-10" data-bs-toggle="collapse" data-bs-target="#edit-{{ $address->id }}" aria-label="Close"></button>
                                    </div>
                                    @include('account._address-form', ['address' => $address, 'action' => route('account.addresses.update', $address), 'method' => 'PUT'])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
