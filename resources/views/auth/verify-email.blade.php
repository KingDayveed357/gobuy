@extends('layouts.storefront')

@section('title', 'Verify your email — gobuy')

@section('content')
    <section class="pt-6 pb-9">
        <div class="container-small">
            <div class="row justify-content-center">
                <div class="col-sm-10 col-md-7 col-lg-5">
                    <div class="card">
                        <div class="card-body p-4 p-sm-5 text-center">
                            <div class="mb-3"><span class="fas fa-envelope-open-text fs-5 text-primary"></span></div>
                            <h3 class="mb-1">Verify your email</h3>
                            <p class="text-body-tertiary mb-4">We've sent a 6-digit code to <span class="fw-semibold">{{ auth()->user()->email }}</span>.</p>

                            @if (session('status'))
                                <div class="alert alert-subtle-success">{{ session('status') }}</div>
                            @endif
                            @if (session('resend_error'))
                                <div class="alert alert-subtle-warning">{{ session('resend_error') }}</div>
                            @endif
                            @error('code')
                                <div class="alert alert-subtle-danger">{{ $message }}</div>
                            @enderror

                            <form action="{{ route('verification.verify') }}" method="POST">
                                @csrf
                                <input class="form-control form-control-lg text-center mb-3" type="text" name="code"
                                       inputmode="numeric" maxlength="6" placeholder="••••••" autocomplete="one-time-code" required autofocus
                                       style="letter-spacing: 0.5rem; font-size: 1.5rem;">
                                <button class="btn btn-primary w-100 mb-3" type="submit">Verify email</button>
                            </form>

                            <form action="{{ route('verification.resend') }}" method="POST" id="resend-form">
                                @csrf
                                <p class="fs-9 mb-0">Didn't get a code?
                                    <button type="submit" id="resend-btn" class="btn btn-link p-0 fs-9 align-baseline">Resend code</button>
                                </p>
                                <p id="resend-countdown" class="fs-9 text-body-tertiary mt-1 mb-0" style="display:none;">
                                    Resend available in <span id="resend-timer"></span>s
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // Seconds remaining as computed server-side — survives page refresh reliably.
    const serverSeconds = parseInt('{{ $secondsRemaining }}', 10);

    const btn       = document.getElementById('resend-btn');
    const countdown = document.getElementById('resend-countdown');
    const timerSpan = document.getElementById('resend-timer');
    const storageKey = 'otp_resend_until_{{ auth()->id() }}';

    function startCountdown(seconds) {
        if (seconds <= 0) return;

        // Persist the absolute expiry so other tabs and refreshes can recover.
        const expiresAt = Date.now() + seconds * 1000;
        localStorage.setItem(storageKey, expiresAt);

        disable(seconds);
    }

    function disable(seconds) {
        btn.disabled = true;
        btn.style.display = 'none';
        countdown.style.display = 'block';
        timerSpan.textContent = seconds;

        const interval = setInterval(function () {
            seconds -= 1;
            timerSpan.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(interval);
                localStorage.removeItem(storageKey);
                enable();
            }
        }, 1000);
    }

    function enable() {
        btn.disabled = false;
        btn.style.display = '';
        countdown.style.display = 'none';
    }

    // On page load: server value takes precedence; localStorage is a fallback
    // for mid-session tab switches where the server is not re-queried.
    if (serverSeconds > 0) {
        startCountdown(serverSeconds);
    } else {
        // Check localStorage in case the user is on a second tab that didn't
        // just trigger a resend — keeps the UX consistent across tabs.
        const stored = parseInt(localStorage.getItem(storageKey) || '0', 10);
        const remaining = Math.ceil((stored - Date.now()) / 1000);
        if (remaining > 0) {
            disable(remaining);
        }
    }
}());
</script>
@endpush
