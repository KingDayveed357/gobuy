<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    @include('partials.head')
</head>

<body>
    <main class="d-flex flex-center min-vh-100 py-5">
        <div class="container-small" style="max-width: 420px;">
            <div class="text-center mb-4">
                <h3 class="logo-text text-primary mb-1">gobuy <span class="fs-9 text-body-tertiary">admin</span></h3>
                <p class="text-body-tertiary mb-0">Sign in to the admin console</p>
            </div>

            <div class="card">
                <div class="card-body p-4 p-sm-5">
                    @if ($errors->any())
                        <div class="alert alert-subtle-danger">{{ $errors->first() }}</div>
                    @endif

                    <form action="{{ route('admin.login') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input class="form-control" type="password" name="password" required>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                            <label class="form-check-label fs-9" for="remember">Keep me signed in</label>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Sign in</button>
                    </form>
                </div>
            </div>

            <p class="text-center fs-9 text-body-tertiary mt-3">Authorized personnel only.</p>
        </div>
    </main>

    @include('partials.scripts')
</body>

</html>
