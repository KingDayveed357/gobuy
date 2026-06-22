@extends('layouts.storefront')

@section('title', 'My addresses — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Addresses</li>
                </ol>
            </nav>

            <div class="d-flex flex-between-center mb-4">
                <div>
                    <h2 class="mb-1">My addresses</h2>
                    <p class="text-body-tertiary mb-0">Saved addresses speed up your checkout.</p>
                </div>
                <a class="btn btn-primary" data-bs-toggle="collapse" href="#addAddress" role="button"><span class="fas fa-plus me-2"></span>Add address</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-subtle-danger">
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="collapse {{ $errors->any() && ! old('_edit') ? 'show' : '' }} mb-4" id="addAddress">
                <div class="card"><div class="card-body">
                    <h5 class="mb-3">New address</h5>
                    @include('account._address-form', ['action' => route('account.addresses.store')])
                </div></div>
            </div>

            @if ($addresses->isEmpty())
                <div class="card"><div class="card-body text-center py-6">
                    <div class="mb-2"><span class="fas fa-location-dot fs-5 text-body-tertiary"></span></div>
                    <h5 class="mb-1">No addresses yet</h5>
                    <p class="text-body-tertiary mb-0">Add an address to check out faster.</p>
                </div></div>
            @else
                <div class="row g-3">
                    @foreach ($addresses as $address)
                        <div class="col-md-6">
                            <div class="card h-100"><div class="card-body">
                                <div class="d-flex flex-between-center mb-2">
                                    <h6 class="mb-0">{{ $address->label ?: 'Address' }}</h6>
                                    <div class="d-flex gap-1">
                                        @if ($address->is_default_shipping)<span class="badge badge-phoenix badge-phoenix-success">Default shipping</span>@endif
                                        @if ($address->is_default_billing)<span class="badge badge-phoenix badge-phoenix-info">Default billing</span>@endif
                                    </div>
                                </div>
                                <p class="fs-9 mb-1 fw-semibold">{{ $address->recipient_name }} · {{ $address->phone }}</p>
                                <p class="fs-9 text-body-secondary mb-3">{{ $address->formatted() }}</p>

                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-phoenix-secondary" data-bs-toggle="collapse" href="#edit-{{ $address->id }}">Edit</a>
                                    @unless ($address->is_default_shipping)
                                        <form action="{{ route('account.addresses.default', $address) }}" method="POST">
                                            @csrf<input type="hidden" name="purpose" value="shipping">
                                            <button class="btn btn-sm btn-phoenix-secondary" type="submit">Set shipping default</button>
                                        </form>
                                    @endunless
                                    @unless ($address->is_default_billing)
                                        <form action="{{ route('account.addresses.default', $address) }}" method="POST">
                                            @csrf<input type="hidden" name="purpose" value="billing">
                                            <button class="btn btn-sm btn-phoenix-secondary" type="submit">Set billing default</button>
                                        </form>
                                    @endunless
                                    <form action="{{ route('account.addresses.destroy', $address) }}" method="POST" onsubmit="return confirm('Remove this address?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm text-danger" type="submit"><span class="fas fa-trash"></span></button>
                                    </form>
                                </div>

                                <div class="collapse mt-3" id="edit-{{ $address->id }}">
                                    <hr class="border-translucent">
                                    @include('account._address-form', ['address' => $address, 'action' => route('account.addresses.update', $address), 'method' => 'PUT'])
                                </div>
                            </div></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
