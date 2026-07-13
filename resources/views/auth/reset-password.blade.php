@extends('layouts.storefront')

@section('title', 'Set a new password — Quintessential Mart')

@section('content')
    <section class="pt-6 pb-9">
        <div class="container-small">
            <div class="row justify-content-center">
                <div class="col-sm-10 col-md-7 col-lg-5">
                    <div class="card">
                        <div class="card-body p-4 p-sm-5">
                            <h3 class="mb-1 text-center">Set a new password</h3>
                            <p class="text-body-tertiary text-center mb-4">Choose a strong password for your account.</p>

                            @if ($errors->any())
                                <div class="alert alert-subtle-danger">
                                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                                </div>
                            @endif

                            <form action="{{ route('password.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input class="form-control" type="email" name="email" value="{{ old('email', $email) }}" required autofocus>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New password</label>
                                    <input class="form-control" type="password" name="password" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Confirm new password</label>
                                    <input class="form-control" type="password" name="password_confirmation" required>
                                </div>
                                <button class="btn btn-primary w-100" type="submit">Reset password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
