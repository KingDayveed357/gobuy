@extends('layouts.storefront')

@section('title', 'Sign in — Quintessential Mart')

@section('content')
    <section class="pt-6 pb-9">
        <div class="container-small">
            <div class="row justify-content-center">
                <div class="col-sm-10 col-md-7 col-lg-5">
                    <div class="card">
                        <div class="card-body p-4 p-sm-5">
                            <h3 class="mb-1 text-center">Welcome back</h3>
                            <p class="text-body-tertiary text-center mb-4">Sign in to your Quintessential Mart account.</p>

                            @if (session('status'))
                                <div class="alert alert-subtle-success">{{ session('status') }}</div>
                            @endif
                            @error('email')
                                <div class="alert alert-subtle-danger">{{ $message }}</div>
                            @enderror

                            <form action="{{ route('login') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex flex-between-center">
                                        <label class="form-label mb-0">Password</label>
                                        <a class="fs-9" href="{{ route('password.request') }}">Forgot password?</a>
                                    </div>
                                    <input class="form-control mt-1" type="password" name="password" required>
                                </div>
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                                    <label class="form-check-label fs-9" for="remember">Keep me signed in</label>
                                </div>
                                <button class="btn btn-primary w-100 mb-3" type="submit">Sign in</button>
                                <p class="text-center mb-0 fs-9">New to Quintessential Mart? <a href="{{ route('register') }}">Create an account</a></p>
                            </form>

                            <x-social-auth-buttons />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
