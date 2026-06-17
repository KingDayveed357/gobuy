<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navigation-type="default" data-navbar-horizontal-shape="default">

<head>
    @include('partials.head')
    <link href="{{ asset('theme/css/admin.css') }}" type="text/css" rel="stylesheet">
</head>

<body>
    <main class="main" id="top">
        @include('admin.partials.navbar-vertical')
        @include('admin.partials.navbar-top')

        <div class="content">
            <div class="container-fluid px-3 px-lg-4 px-xxl-5 py-3 py-lg-4">
                @if (session('status'))
                    <div class="alert alert-subtle-success d-flex align-items-center" role="alert">
                        <span class="fas fa-circle-check me-2"></span>{{ session('status') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-subtle-danger d-flex align-items-center" role="alert">
                        <span class="fas fa-circle-exclamation me-2"></span>{{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </main>

<script>
    window.config = window.config || {};
    window.config.mapboxToken = "{{ config('services.mapbox.token') }}";
</script>
    @include('partials.scripts')
    <script src="{{ asset('theme/js/admin.js') }}"></script>


    {{-- Loading state on POST form submission. --}}
    <script>
        document.addEventListener('submit', function (e) {
            if (e.target.matches('form') && (e.target.method || '').toLowerCase() === 'post') {
                var btn = e.target.querySelector('button[type="submit"]');
                if (btn) { btn.classList.add('is-loading'); }
            }
        }, true);
    </script>
</body>

</html>
