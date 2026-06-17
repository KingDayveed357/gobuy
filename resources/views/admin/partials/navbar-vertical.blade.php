@php($admin = auth('admin')->user())

<nav class="navbar navbar-vertical navbar-expand-lg">
    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content">
            <ul class="navbar-nav flex-column" id="navbarVerticalNav">
                <li class="nav-item">
                    <div class="nav-item-wrapper">
                        <a class="nav-link label-1 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span data-feather="pie-chart"></span></span><span class="nav-link-text">Dashboard</span>
                            </div>
                        </a>
                    </div>
                </li>

                @if ($admin->can('manage_products') || $admin->can('manage_orders') || $admin->can('manage_payments'))
                    <li class="nav-item">
                        <p class="navbar-vertical-label">Commerce</p>
                        <hr class="navbar-vertical-line">
                    </li>
                    @if ($admin->can('manage_products'))
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="box"></span></span><span class="nav-link-text">Products</span></div>
                                </a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="tag"></span></span><span class="nav-link-text">Categories</span></div>
                                </a>
                            </div>
                        </li>
                    @endif
                    @if ($admin->can('manage_orders'))
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="shopping-cart"></span></span><span class="nav-link-text">Orders</span></div>
                                </a>
                            </div>
                        </li>
                    @endif
                    @if ($admin->can('manage_payments'))
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="credit-card"></span></span><span class="nav-link-text">Payments</span></div>
                                </a>
                            </div>
                        </li>
                    @endif
                @endif

                @if ($admin->can('view_analytics'))
                    <li class="nav-item">
                        <p class="navbar-vertical-label">Insights</p>
                        <hr class="navbar-vertical-line">
                    </li>
                    <li class="nav-item">
                        <div class="nav-item-wrapper">
                            <a class="nav-link label-1 {{ request()->routeIs('admin.analytics') ? 'active' : '' }}" href="{{ route('admin.analytics') }}">
                                <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="bar-chart-2"></span></span><span class="nav-link-text">Analytics</span></div>
                            </a>
                        </div>
                    </li>
                @endif

                @if ($admin->can('manage_customers'))
                    <li class="nav-item">
                        <p class="navbar-vertical-label">People</p>
                        <hr class="navbar-vertical-line">
                    </li>
                    <li class="nav-item">
                        <div class="nav-item-wrapper">
                            <a class="nav-link label-1 {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">
                                <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="users"></span></span><span class="nav-link-text">Customers</span></div>
                            </a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-item-wrapper">
                            <a class="nav-link label-1 {{ request()->routeIs('admin.wholesale.*') ? 'active' : '' }}" href="{{ route('admin.wholesale.index') }}">
                                <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="briefcase"></span></span><span class="nav-link-text">Wholesale</span></div>
                            </a>
                        </div>
                    </li>
                @endif
            </ul>
        </div>
    </div>
    <div class="navbar-vertical-footer">
        <button class="btn navbar-vertical-toggle border-0 fw-semibold w-100 white-space-nowrap d-flex align-items-center">
            <span class="fas fa-angles-left fs-8"></span>
            <span class="fas fa-angles-right fs-8"></span>
            <span class="navbar-vertical-footer-text ms-2">Collapsed View</span>
        </button>
    </div>
</nav>
