@php($admin = auth('admin')->user())
@php($unread = $admin->unreadNotifications)

<nav class="navbar navbar-top fixed-top navbar-expand" id="navbarDefault">
    <div class="collapse navbar-collapse justify-content-between">
        <div class="navbar-logo">
            <button class="btn navbar-toggler navbar-toggler-humburger-icon hover-bg-transparent" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse"
                    aria-expanded="false" aria-label="Toggle Navigation">
                <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
            </button>
            <a class="navbar-brand me-1 me-sm-3" href="{{ route('admin.dashboard') }}">
                <h5 class="logo-text text-primary mb-0">gobuy <span class="fs-9 text-body-tertiary">admin</span></h5>
            </a>
        </div>

        @if ($admin->can('manage_products'))
            <form class="search-box navbar-top-search-box d-none d-lg-block" action="{{ route('admin.products.index') }}" method="GET" style="width: 25rem;">
                <div class="position-relative">
                    <input class="form-control search-input rounded-pill form-control-sm" type="search" name="q" value="{{ request('q') }}" placeholder="Search products...">
                    <span class="fas fa-search search-box-icon"></span>
                </div>
            </form>
        @endif

        <ul class="navbar-nav navbar-nav-icons flex-row">
            <li class="nav-item">
                <div class="theme-control-toggle fa-icon-wait px-2">
                    <input class="form-check-input ms-0 theme-control-toggle-input" id="adminThemeToggle" type="checkbox" data-theme-control="phoenixTheme" value="dark">
                    <label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="adminThemeToggle" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Switch to dark theme" style="height:32px;width:32px;"><span class="icon" data-feather="moon"></span></label>
                    <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="adminThemeToggle" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Switch to light theme" style="height:32px;width:32px;"><span class="icon" data-feather="sun"></span></label>
                </div>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link {{ $unread->isNotEmpty() ? 'icon-indicator icon-indicator-danger' : '' }}" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <span class="fas fa-bell" style="font-size:20px;"></span>
                    @if ($unread->isNotEmpty())<span class="icon-indicator-number">{{ $unread->count() }}</span>@endif
                </a>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown-menu py-0 shadow border navbar-dropdown-caret mt-2" style="min-width: 20rem;">
                    <div class="card position-relative border-0">
                        <div class="card-header p-2 d-flex flex-between-center">
                            <h5 class="text-body-emphasis mb-0">Notifications</h5>
                            @if ($unread->isNotEmpty())
                                <form action="{{ route('admin.notifications.read') }}" method="POST">@csrf
                                    <button class="btn btn-link p-0 fs-10 fw-normal" type="submit">Mark all read</button>
                                </form>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            <div class="scrollbar-overlay" style="max-height: 22rem;">
                                @forelse ($admin->notifications()->latest()->take(8)->get() as $note)
                                    @if (isset($note->data['order_number']))
                                        <a class="px-3 py-3 d-block border-bottom border-translucent text-decoration-none {{ $note->read_at ? '' : 'bg-primary-subtle bg-opacity-25' }}" href="{{ route('admin.orders.show', $note->data['order_number']) }}">
                                            <p class="fs-9 text-body-emphasis mb-1 fw-semibold"><span class="fas fa-receipt text-primary me-2"></span>New paid order {{ $note->data['order_number'] }}</p>
                                            <p class="fs-9 text-body-tertiary mb-0">{{ $note->data['customer'] ?? 'Unknown' }} · {{ money($note->data['total_kobo'] ?? 0) }} · {{ $note->created_at->diffForHumans() }}</p>
                                        </a>
                                    @endif
                                @empty
                                    <p class="text-center text-body-tertiary fs-9 py-4 mb-0">No notifications.</p>
                                @endforelse
                            </div>
                        </div>
                        <div class="card-footer p-0 border-top text-center">
                            <a class="fw-bold fs-10 d-block py-2" href="{{ route('admin.notifications.index') }}">View all</a>
                        </div>
                    </div>
                </div>
            </li>

            <li class="nav-item dropdown">
                <a class="nav-link lh-1 pe-0" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="avatar avatar-l">
                        <div class="avatar-name rounded-circle bg-primary-subtle"><span class="text-primary fw-bold">{{ $admin->initials() }}</span></div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border mt-2">
                    <div class="card position-relative border-0">
                        <div class="card-body p-0">
                            <div class="text-center pt-4 pb-3">
                                <div class="avatar avatar-xl"><div class="avatar-name rounded-circle bg-primary-subtle"><span class="text-primary fw-bold fs-7">{{ $admin->initials() }}</span></div></div>
                                <h6 class="mt-2 text-body-emphasis mb-0">{{ $admin->name }}</h6>
                                <p class="fs-9 text-body-tertiary mb-0">{{ $admin->getRoleNames()->first() ?? 'Admin' }}</p>
                            </div>
                        </div>
                        <div class="card-footer p-0 border-top border-translucent">
                            <div class="px-3 py-2"><p class="fs-9 text-body-tertiary mb-0">{{ $admin->email }}</p></div>
                            <hr class="my-0">
                            <div class="px-3 ">
                                <a href="{{ route('admin.settings') }}" class="btn btn-link px-0 text-decoration-none">
                                    <span class="me-2 text-body-tertiary" data-feather="user"></span>Account
                                </a>
                            </div>
                            <div class="px-3 pb-2">
                                <a href="{{ route('admin.settings.store') }}" class="btn btn-link px-0 text-decoration-none">
                                    <span class="me-2 text-body-tertiary" data-feather="shopping-bag"></span>Store settings
                                </a>
                            </div>
                            <hr class="my-0">
                            <div class="px-3 py-3">
                                <form action="{{ route('admin.logout') }}" method="POST">@csrf
                                    <button class="btn btn-phoenix-secondary d-flex flex-center w-100" type="submit"><span class="me-2" data-feather="log-out"></span>Sign out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>
