@extends('layouts.storefront')

@section('title', 'Apply for wholesale — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <nav class="mb-3" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Wholesale application</li>
                        </ol>
                    </nav>
                    <h2 class="mb-2">Apply for wholesale pricing</h2>
                    <p class="text-body-tertiary mb-4">Once approved, wholesale prices apply automatically at qualifying quantities.</p>

                    @if ($user->hasPendingWholesaleApplication())
                        <div class="alert alert-subtle-warning">Your application is under review. You can update your details below.</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-subtle-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('account.wholesale') }}" method="POST">
                                @csrf
                                @php($profile = $user->wholesaleProfile)
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Business name</label>
                                        <input class="form-control" type="text" name="business_name" value="{{ old('business_name', $profile?->business_name) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">RC number <span class="text-body-tertiary">(optional)</span></label>
                                        <input class="form-control" type="text" name="rc_number" value="{{ old('rc_number', $profile?->rc_number) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Business phone</label>
                                        <input class="form-control" type="text" name="business_phone" value="{{ old('business_phone', $profile?->business_phone) }}" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Business address</label>
                                        <input class="form-control" type="text" name="business_address" value="{{ old('business_address', $profile?->business_address) }}" required>
                                    </div>
                                </div>
                                <button class="btn btn-primary mt-4" type="submit">Submit application</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
