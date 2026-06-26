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

                    @if (! $user->hasVerifiedEmail())
                        <div class="card border-warning text-center">
                            <div class="card-body py-5">
                                <!-- <span class="fas fa-mobile-alt text-warning fs-3 mb-3"></span>
                                <h4 class="mb-2">Verify your account</h4>
                                <p class="text-body-tertiary mb-4">You need to verify your account with an OTP code before you can apply for a wholesale account.</p> -->
                                  <span class="fas fa-envelope-open-text text-warning fs-3 mb-3"></span>
                                <h4 class="mb-2">Verify your email address</h4>
                                <p class="text-body-tertiary mb-4">You need to verify your email address before you can apply for a wholesale account.</p>
                                <div class="d-flex justify-content-center gap-2">
                                    <form action="{{ route('verification.resend') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-warning">Send verification code</button>
                                    </form>
                                    <a href="{{ route('verification.notice') }}" class="btn btn-outline-warning">Enter existing code</a>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('account.wholesale') }}" method="POST" enctype="multipart/form-data">
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
                                        <div class="col-md-6">
                                            <label class="form-label">Industry <span class="text-body-tertiary">(optional)</span></label>
                                            <input class="form-control" type="text" name="industry" value="{{ old('industry', $profile?->industry) }}" placeholder="e.g. Construction, Oil & Gas">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Business address</label>
                                            <input class="form-control" type="text" name="business_address" value="{{ old('business_address', $profile?->business_address) }}" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Why do you want a wholesale account?</label>
                                            <textarea class="form-control" name="intent" rows="3" required placeholder="Tell us about your buying needs and expected volumes…">{{ old('intent', $profile?->intent) }}</textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Supporting documents <span class="text-body-tertiary">(optional — CAC certificate, utility bill)</span></label>
                                            <input class="form-control" type="file" name="documents[]" accept=".pdf,image/*" multiple>
                                            @if ($profile && $profile->documents()->isNotEmpty())
                                                <p class="fs-9 text-body-tertiary mt-2 mb-0">{{ $profile->documents()->count() }} document(s) already uploaded.</p>
                                            @endif
                                        </div>
                                    </div>
                                    <button class="btn btn-primary mt-4" type="submit">Submit application</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
