<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navbar-horizontal-shape="default">

<head>
    @include('partials.head')
</head>

<body>
    <main class="main" id="top">
        @include('partials.storefront-nav')

        @if (session('status'))
            <div class="container-small mt-3">
                <div class="alert alert-subtle-success" role="alert">{{ session('status') }}</div>
            </div>
        @endif

        @yield('content')

        @include('partials.footer')
    </main>

    @include('partials.scripts')
</body>

</html>
