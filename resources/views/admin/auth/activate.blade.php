<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    @include('partials.head')
    <title>Set up your account — gobuy admin</title>
</head>

<body>
    <main class="d-flex flex-center min-vh-100 py-5">
        <div class="container-small" style="max-width: 420px;">
            <div class="text-center mb-4">
                <h3 class="logo-text text-primary mb-1">gobuy <span class="fs-9 text-body-tertiary">admin</span></h3>
                <p class="text-body-tertiary mb-0">Welcome, {{ $staff->name }} — set a password to finish.</p>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-subtle-danger">
                            <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ $action }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input class="form-control" type="email" value="{{ $staff->email }}" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New password</label>
                            <input class="form-control" type="password" name="password" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm password</label>
                            <input class="form-control" type="password" name="password_confirmation" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Activate my account</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
