@extends('layouts.storefront')

@section('title', 'Create account — gobuy')

@section('content')
    <section class="pt-6 pb-9">
        <div class="container-small">
            <div class="row justify-content-center">
                <div class="col-sm-10 col-md-7 col-lg-5">
                    <div class="card">
                        <div class="card-body p-4 p-sm-5">
                            <h3 class="mb-1 text-center">Create your account</h3>
                            <p class="text-body-tertiary text-center mb-4">Shop retail or apply for wholesale.</p>

                            @if ($errors->any())
                                <div class="alert alert-subtle-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('register') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Full name</label>
                                    <input class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input class="form-control" type="email" name="email" value="{{ old('email') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone <span class="text-body-tertiary">(optional)</span></label>
                                    <input class="form-control" type="text" name="phone" value="{{ old('phone') }}">
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-sm-6">
                                        <label class="form-label">Password</label>
                                        <input class="form-control" type="password" name="password" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label">Confirm password</label>
                                        <input class="form-control" type="password" name="password_confirmation" required>
                                    </div>
                                </div>
                                <button class="btn btn-primary w-100 mb-3" type="submit">Create account</button>
                                <p class="text-center mb-0 fs-9">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
                            </form>

                            <x-social-auth-buttons />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
