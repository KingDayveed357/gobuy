@extends('layouts.account')

@section('title', 'Apply for wholesale — Quintessential Mart')

@php
    $pageTitle = 'Wholesale';
@endphp

@section('account_content')
    <div class="mb-4">
        <a href="{{ route('account.dashboard') }}" class="btn btn-link px-0 text-body-tertiary mb-2"><span class="fas fa-arrow-left me-2"></span>Back to Dashboard</a>
        <h3 class="mb-1 text-body-emphasis">Apply for Wholesale Pricing</h3>
        <p class="text-body-tertiary mb-0">Once approved, wholesale prices apply automatically at qualifying quantities.</p>
    </div>

    @if ($user->hasPendingWholesaleApplication())
        <div class="alert alert-subtle-warning shadow-sm border-0 mb-4 d-flex align-items-center gap-3">
            <span class="fas fa-clock fs-3 text-warning"></span>
            <div>
                <h6 class="mb-1 text-warning-emphasis">Application Under Review</h6>
                <p class="mb-0 fs-9">You can update your details below while we review your application.</p>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-subtle-danger shadow-sm border-0 mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $user->hasVerifiedEmail())
        <div class="card border-0 shadow-sm border-warning">
            <div class="card-body text-center py-6">
                <span class="fas fa-envelope-open-text text-warning fs-1 mb-3"></span>
                <h4 class="mb-2 text-body-emphasis">Verify your email address</h4>
                <p class="text-body-tertiary mb-4">You need to verify your email address before you can apply for a wholesale account.</p>
                <div class="d-flex justify-content-center gap-2">
                    <form action="{{ route('verification.resend') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">Send verification link</button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <form action="{{ route('account.wholesale') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @php($profile = $user->wholesaleProfile)
                    
                    <h5 class="mb-4">Business Information</h5>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Business Name</label>
                            <input class="form-control" type="text" name="business_name" value="{{ old('business_name', $profile?->business_name) }}" placeholder="Your registered business name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">RC Number <span class="text-body-tertiary fw-normal">(Optional)</span></label>
                            <input class="form-control" type="text" name="rc_number" value="{{ old('rc_number', $profile?->rc_number) }}" placeholder="e.g. RC123456">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Business Phone</label>
                            <input class="form-control" type="tel" name="business_phone" value="{{ old('business_phone', $profile?->business_phone) }}" placeholder="+234 800 000 0000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Industry <span class="text-body-tertiary fw-normal">(Optional)</span></label>
                            <input class="form-control" type="text" name="industry" value="{{ old('industry', $profile?->industry) }}" placeholder="e.g. Construction, Retail, Oil & Gas">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Business Address</label>
                            <input class="form-control" type="text" name="business_address" value="{{ old('business_address', $profile?->business_address) }}" placeholder="Full physical address of your business" required>
                        </div>
                    </div>

                    <hr class="border-translucent mb-4">
                    <h5 class="mb-4">Application Details</h5>

                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-bold">Why do you want a wholesale account?</label>
                            <textarea class="form-control" name="intent" rows="4" required placeholder="Tell us about your buying needs, expected volumes, and business goals...">{{ old('intent', $profile?->intent) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Supporting Documents <span class="text-body-tertiary fw-normal">(Optional — CAC certificate, utility bill)</span></label>
                            <input class="form-control" type="file" name="documents[]" accept=".pdf,image/*" multiple>
                            @if ($profile && $profile->documents()->isNotEmpty())
                                <p class="fs-9 text-success fw-semibold mt-2 mb-0"><span class="fas fa-check-circle me-1"></span>{{ $profile->documents()->count() }} document(s) already uploaded.</p>
                            @endif
                            <div class="form-text mt-2">You can select multiple files. Acceptable formats: PDF, JPG, PNG.</div>
                        </div>
                    </div>

                    <div class="text-end pt-3 border-top border-translucent">
                        <button class="btn btn-primary px-5" type="submit">{{ $user->hasPendingWholesaleApplication() ? 'Update Application' : 'Submit Application' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
