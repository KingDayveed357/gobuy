@extends('layouts.storefront')

@section('title', 'Reset password — Quintessential Mart')

@section('content')
    <section class="pt-6 pb-9">
        <div class="container-small">
            <div class="row justify-content-center">
                <div class="col-sm-10 col-md-7 col-lg-5">
                    <div class="card">
                        <div class="card-body p-4 p-sm-5">
                            <h3 class="mb-1 text-center">Forgot your password?</h3>
                            <p class="text-body-tertiary text-center mb-4">Enter your email and we'll send you a reset link.</p>

                            @if (session('status'))
                                <div class="alert alert-subtle-success">{{ session('status') }}</div>
                            @endif
                            @error('email')
                                <div class="alert alert-subtle-danger">{{ $message }}</div>
                            @enderror

                            <form action="{{ route('password.email') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus>
                                </div>
                                <button class="btn btn-primary w-100 mb-3" type="submit">Email reset link</button>
                                <p class="text-center mb-0 fs-9"><a href="{{ route('login') }}">Back to sign in</a></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
