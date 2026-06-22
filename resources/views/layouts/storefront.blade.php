<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navbar-horizontal-shape="default">

<head>
    @include('partials.head')
    <link href="{{ asset('theme/css/toast.css') }}" type="text/css" rel="stylesheet">
</head>

<body>
    <main class="main" id="top">
        @include('partials.storefront-nav')

        {{-- Flash messages are handled by Toast via JS below --}}

        @yield('content')

        @include('partials.footer')
    </main>

    @include('partials.scripts')
    <script src="{{ asset('theme/js/toast.js') }}"></script>
    @include('partials.wishlist-script')
    @include('partials.search-script')

    {{-- Trigger Premium Toast Notifications --}}
    @if(session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Toast.success("{{ session('status') }}");
            });
        </script>
    @endif
    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Toast.error("{{ session('error') }}");
            });
        </script>
    @endif
</body>

</html>
