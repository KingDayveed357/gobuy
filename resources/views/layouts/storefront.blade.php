<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navbar-horizontal-shape="default">

<head>
    @include('partials.head')
    <link href="{{ asset('theme/css/toast.css') }}" type="text/css" rel="stylesheet">
    @livewireStyles
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
    @livewireScripts
    @include('partials.wishlist-script')
    @include('partials.cart-script')
    @include('partials.search-script')

    {{-- Bridge: let any Livewire component raise a Toast via $this->dispatch('toast', type, message). --}}
    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('toast', function (e) {
                var p = Array.isArray(e) ? e[0] : e;
                if (window.Toast && p && typeof Toast[p.type] === 'function') {
                    Toast[p.type](p.message);
                }
            });
        });
    </script>

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
    @if($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Toast.error(@json($errors->first()));
            });
        </script>
    @endif
</body>

</html>
