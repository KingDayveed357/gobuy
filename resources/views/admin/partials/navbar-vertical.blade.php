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
                                <a class="nav-link label-1 {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}" href="{{ route('admin.inventory.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="layers"></span></span><span class="nav-link-text">Inventory</span></div>
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
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="star"></span></span><span class="nav-link-text">Reviews</span></div>
                                </a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}" href="{{ route('admin.banners.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="image"></span></span><span class="nav-link-text">Banners</span></div>
                                </a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="gift"></span></span><span class="nav-link-text">Coupons</span></div>
                                </a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.promotions.*') ? 'active' : '' }}" href="{{ route('admin.promotions.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="zap"></span></span><span class="nav-link-text">Promotions</span></div>
                                </a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.pricing.bulk.*') ? 'active' : '' }}" href="{{ route('admin.pricing.bulk.create') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="trending-up"></span></span><span class="nav-link-text">Bulk pricing</span></div>
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
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.shipments.*') || request()->routeIs('admin.logistics.*') ? 'active' : '' }}" href="{{ route('admin.shipments.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="truck"></span></span><span class="nav-link-text">Dispatch</span></div>
                                </a>
                            </div>
                        </li>
                    @endif
                    @if ($admin->can('manage_returns'))
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}" href="{{ route('admin.returns.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="rotate-ccw"></span></span><span class="nav-link-text">Returns</span></div>
                                </a>
                            </div>
                        </li>
                    @endif
                    @if ($admin->can('manage_refunds'))
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.store-credits.*') ? 'active' : '' }}" href="{{ route('admin.store-credits.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="credit-card"></span></span><span class="nav-link-text">Store credit</span></div>
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
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.transfers.*') ? 'active' : '' }}" href="{{ route('admin.transfers.index') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="building"></span></span><span class="nav-link-text">Transfers</span></div>
                                </a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <div class="nav-item-wrapper">
                                <a class="nav-link label-1 {{ request()->routeIs('admin.reconciliation') ? 'active' : '' }}" href="{{ route('admin.reconciliation') }}">
                                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span data-feather="check-square"></span></span><span class="nav-link-text">Reconciliation</span></div>
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
            <!-- <span class="fas fa-angles-right fs-8"></span> -->
            <span class="navbar-vertical-footer-text ms-2">Collapsed View</span>
        </button>
    </div>
</nav>
