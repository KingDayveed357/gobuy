@extends('layouts.storefront')

@section('title', 'Track your order — Quintessential Mart')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <h2 class="mb-4 text-center">Track your order</h2>

                    @if (session('error'))
                        <div class="alert alert-subtle-danger">{{ session('error') }}</div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('orders.track') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Order number</label>
                                    <input class="form-control" type="text" name="order_number"
                                           value="{{ old('order_number') }}" placeholder="GB-260615-XXXXX" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Email used at checkout</label>
                                    <input class="form-control" type="email" name="email" value="{{ old('email') }}" required>
                                </div>
                                <button class="btn btn-primary w-100" type="submit">Track order</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
