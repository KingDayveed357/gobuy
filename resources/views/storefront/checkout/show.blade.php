@extends('layouts.storefront')

@section('title', 'Checkout — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('cart.index') }}">Cart</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                </ol>
            </nav>
            <h2 class="mb-5">Check out</h2>

            @if (session('error'))
                <div class="alert alert-subtle-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('checkout.store') }}" method="POST">
                @csrf
                <div class="row justify-content-between">
                    <div class="col-lg-7">
                        @if ($errors->any())
                            <div class="alert alert-subtle-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <h3 class="mb-4">Delivery details</h3>
                        <div class="row g-3 mb-5">
                            <div class="col-md-6">
                                <label class="form-label">Full name</label>
                                <input class="form-control" type="text" name="customer_name"
                                       value="{{ old('customer_name', auth()->user()?->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input class="form-control" type="email" name="customer_email"
                                       value="{{ old('customer_email', auth()->user()?->email) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input class="form-control" type="text" name="customer_phone"
                                       value="{{ old('customer_phone') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">State</label>
                                <input class="form-control" type="text" name="state" value="{{ old('state') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input class="form-control" type="text" name="city" value="{{ old('city') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <input class="form-control" type="text" name="address_line" value="{{ old('address_line') }}" required>
                            </div>
                        </div>

                        <h3 class="mb-3">Payment</h3>
                        <div class="d-flex align-items-center border border-translucent rounded-3 p-3 mb-4">
                            <span class="fas fa-shield-halved text-success fs-5 me-3"></span>
                            <div>
                                <p class="fw-semibold mb-0">Pay securely with Paystack</p>
                                <p class="fs-9 text-body-tertiary mb-0">Card, bank transfer or USSD. You'll be redirected to complete payment.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 col-xl-4">
                        <div class="card mt-3 mt-lg-0">
                            <div class="card-body">
                                <h3 class="mb-3">Summary</h3>
                                <div class="border-bottom border-dashed border-translucent pb-2 mb-2">
                                    @foreach ($lines as $line)
                                        @php($item = $line['item'])
                                        <div class="row align-items-center mb-2 g-2">
                                            <div class="col-8">
                                                <div class="d-flex align-items-center">
                                                    <img class="me-2 border border-translucent rounded-1" src="{{ $item->product->imageUrl() }}" width="36" height="36" style="object-fit: contain;" alt="">
                                                    <h6 class="fw-semibold lh-base mb-0 line-clamp-1">{{ $item->product->name }}</h6>
                                                </div>
                                            </div>
                                            <div class="col-2 text-center"><h6 class="fs-10 mb-0">x{{ $item->quantity }}</h6></div>
                                            <div class="col-2 ps-0"><h6 class="mb-0 fw-semibold text-end">₦{{ number_format($line['lineTotal'], 0) }}</h6></div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="d-flex justify-content-between">
                                    <p class="text-body fw-semibold">Subtotal</p>
                                    <p class="text-body-emphasis fw-semibold">₦{{ number_format($subtotal, 2) }}</p>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <p class="text-body fw-semibold">Delivery</p>
                                    <p class="text-body-emphasis fw-semibold">₦{{ number_format($deliveryFee, 2) }}</p>
                                </div>
                                <div class="d-flex justify-content-between border-y border-dashed border-translucent py-3 mb-4">
                                    <h4 class="mb-0">Total</h4>
                                    <h4 class="mb-0">₦{{ number_format($subtotal + $deliveryFee, 2) }}</h4>
                                </div>
                                <button class="btn btn-primary w-100" type="submit">
                                    Pay ₦{{ number_format($subtotal + $deliveryFee, 2) }}<span class="fas fa-chevron-right ms-1 fs-10"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
