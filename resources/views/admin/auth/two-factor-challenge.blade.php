<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    @include('partials.head')
    <title>Verify it's you — Quintessential Mart admin</title>
</head>

<body>
    <main class="d-flex flex-center min-vh-100 py-5">
        <div class="container-small" style="max-width: 420px;">
            <div class="text-center mb-4">
                <x-brand-logo class="text-primary mb-1 justify-content-center" sub="admin" :size="30" />
                <p class="text-body-tertiary mb-0">Enter the 6-digit code we emailed you.</p>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    @if (session('status'))
                        <div class="alert alert-subtle-success">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-subtle-danger">
                            <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.2fa.verify') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Verification code</label>
                            <input class="form-control form-control-lg text-center" name="code" inputmode="numeric"
                                   autocomplete="one-time-code" maxlength="6" autofocus required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">Verify &amp; sign in</button>
                    </form>

                    <form action="{{ route('admin.2fa.resend') }}" method="POST" class="text-center">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm text-body-tertiary p-0">Didn't get a code? Resend</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
