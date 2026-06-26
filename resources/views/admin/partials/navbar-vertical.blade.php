@php
    $admin = auth('admin')->user();
    $navItems = app(\App\Admin\Support\AdminNavigation::class)->resolve($admin);
@endphp

<nav class="navbar navbar-vertical navbar-expand-lg">
    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content">
            <ul class="navbar-nav flex-column" id="navbarVerticalNav">
                @php
                    $i = 0;
                    $total = count($navItems);
                    $currentLiOpen = false;
                @endphp

                @foreach ($navItems as $index => $entry)
                    @if ($entry['type'] === 'section')
                        {{-- Close any open <li> before starting a new section --}}
                        @if ($currentLiOpen)
                            </li>
                            @php $currentLiOpen = false; @endphp
                        @endif

                        {{-- Open a new <li> for this section and its children --}}
                        <li class="nav-item">
                            <p class="navbar-vertical-label">{{ $entry['label'] }}</p>
                            <hr class="navbar-vertical-line">
                        @php $currentLiOpen = true; @endphp

                    @elseif ($entry['type'] === 'group')
                        {{-- If no open <li> yet (group before any section), open one --}}
                        @if (! $currentLiOpen)
                            <li class="nav-item">
                            @php $currentLiOpen = true; @endphp
                        @endif

                        <x-admin.nav.group :group="$entry" />

                    @elseif ($entry['type'] === 'link')
                        {{-- If no open <li> yet, open one --}}
                        @if (! $currentLiOpen)
                            <li class="nav-item">
                            @php $currentLiOpen = true; @endphp
                        @endif

                        <x-admin.nav.link :item="$entry" />
                    @endif

                    {{-- Close the <li> on the last item --}}
                    @if ($loop->last && $currentLiOpen)
                        </li>
                        @php $currentLiOpen = false; @endphp
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
    <div class="navbar-vertical-footer">
        <button class="btn navbar-vertical-toggle border-0 fw-semibold w-100 white-space-nowrap d-flex align-items-center">
            <span class="fas fa-angles-left navbar-vertical-toggle-icon"></span>
            <span class="navbar-vertical-footer-text ms-2">Collapsed View</span>
        </button>
    </div>
</nav>
