<!DOCTYPE html>
<html lang="en-US" dir="ltr" data-navigation-type="default" data-navbar-horizontal-shape="default">

<head>
    @include('partials.head')
    <link href="{{ asset('theme/css/admin.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('theme/css/toast.css') }}" type="text/css" rel="stylesheet">
    @livewireStyles
</head>

<body>
    <main class="main" id="top">
        @include('admin.partials.navbar-vertical')
        @include('admin.partials.navbar-top')

        <div class="content">
            <div class="container-fluid px-3 px-lg-4 px-xxl-5 py-3 py-lg-4">
                {{-- Flash messages are handled by Toast via JS below --}}

                @yield('content')
            </div>
        </div>
    </main>

<script>
    window.config = window.config || {};
    window.config.mapboxToken = "{{ config('services.mapbox.token') }}";
</script>
    @include('partials.scripts')
    <script src="{{ asset('theme/js/toast.js') }}"></script>
    <script src="{{ asset('theme/js/admin.js') }}"></script>

    {{-- Bridge: any admin Livewire component can raise a Toast via $this->dispatch('toast', type, message). --}}
    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('toast', function (e) {
                var p = Array.isArray(e) ? e[0] : e;
                var type = (p && p.type) || 'info';
                if (window.Toast && typeof Toast[type] === 'function') { Toast[type](p.message); }
                else if (window.Toast) { Toast.info(p.message); }
            });
        });

        // Use a MutationObserver to instantly restore layout attributes if Livewire strips them during wire:navigate.
        // This runs synchronously before the browser paints, completely eliminating any theme flash.
        (function() {
            var observer = new MutationObserver(function() {
                var el = document.documentElement;
                
                var theme = localStorage.getItem('phoenixTheme');
                if (theme) {
                    var expectedTheme = theme === 'auto' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : theme;
                    if (el.getAttribute('data-bs-theme') !== expectedTheme) {
                        el.setAttribute('data-bs-theme', expectedTheme);
                    }
                }
                
                var isCollapsed = JSON.parse(localStorage.getItem('phoenixIsNavbarVerticalCollapsed'));
                if (isCollapsed && !el.classList.contains('navbar-vertical-collapsed')) {
                    el.classList.add('navbar-vertical-collapsed');
                } else if (!isCollapsed && el.classList.contains('navbar-vertical-collapsed')) {
                    el.classList.remove('navbar-vertical-collapsed');
                }
                
                var navPos = localStorage.getItem('phoenixNavbarPosition');
                if (navPos) {
                    var expectedNavPos = (navPos === 'horizontal' || navPos === 'combo') ? navPos : 'default';
                    if (el.getAttribute('data-navigation-type') !== expectedNavPos) {
                        el.setAttribute('data-navigation-type', expectedNavPos);
                    }
                }
            });

            observer.observe(document.documentElement, { 
                attributes: true, 
                attributeFilter: ['data-bs-theme', 'class', 'data-navigation-type'] 
            });
        })();
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


    {{-- Loading state on POST form submission. --}}
    <script>
        document.addEventListener('submit', function (e) {
            if (e.target.matches('form') && (e.target.method || '').toLowerCase() === 'post') {
                var btn = e.target.querySelector('button[type="submit"]');
                if (btn) { btn.classList.add('is-loading'); }
            }
        }, true);
    </script>
    <x-admin.action-modal />
    @include('admin.partials.promote-modal')

    {{-- Global Command Palette (Ctrl+K) — lazy-loaded on first trigger --}}
    @include('admin.partials.command-palette')

    {{-- Web Push (PWA) client — admin subscription endpoints. --}}
    @include('partials.push-notifications', [
        'pushStoreUrl' => route('admin.push-subscriptions.store'),
        'pushDeleteUrl' => route('admin.push-subscriptions.destroy'),
    ])
    @livewireScripts
</body>

</html>
