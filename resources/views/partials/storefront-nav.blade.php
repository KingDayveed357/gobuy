{{-- =====================================================================
     GoBuy Storefront Navigation
     Top bar: logo · search (constrained) · icons
     Secondary bar: mega-menu · nav links
     ===================================================================== --}}

{{-- ---- Top Bar ---- --}}
<section class="py-0 bg-body border-bottom border-translucent">
    <div class="container-small">
        <nav class="navbar navbar-expand px-0 py-2" style="gap:0.75rem;" aria-label="Main navigation">

            {{-- Logo area (left) --}}
            <div class="d-flex align-items-center" style="width: 280px;">
                <a class="navbar-brand text-decoration-none flex-shrink-0" href="{{ route('home') }}">
                    <h3 class="logo-text text-primary mb-0 fw-bold" style="letter-spacing: -0.5px;">gobuy</h3>
                </a>
            </div>

            {{-- Spacer for mobile (pushes icons right when search is hidden) --}}
            <div class="flex-grow-1 d-md-none"></div>

            {{-- Search area (centered on desktop) --}}
            <div class="d-none d-md-flex flex-grow-1 justify-content-center">
                <div style="max-width:480px; width:100%;">
                    <x-search-box />
                </div>
            </div>

            {{-- Icon group (right) --}}
            <div class="d-flex align-items-center justify-content-end" style="width: 280px;">
                <ul class="navbar-nav flex-row align-items-center flex-shrink-0" style="gap:0.15rem;">

                    {{-- Theme toggle --}}
                    <li class="nav-item d-flex align-items-center">
                        <div class="theme-control-toggle feather-icon-wait px-1">
                            <input class="form-check-input ms-0 theme-control-toggle-input"
                                   type="checkbox"
                                   data-theme-control="phoenixTheme"
                                   value="dark"
                                   id="themeControlToggle">
                            <label class="mb-0 theme-control-toggle-label theme-control-toggle-light"
                                   for="themeControlToggle"
                                   data-bs-toggle="tooltip"
                                   data-bs-placement="bottom"
                                   data-bs-title="Switch to dark theme"
                                   style="height:32px;width:32px;">
                                <span class="icon" data-feather="moon"></span>
                            </label>
                            <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark"
                                   for="themeControlToggle"
                                   data-bs-toggle="tooltip"
                                   data-bs-placement="bottom"
                                   data-bs-title="Switch to light theme"
                                   style="height:32px;width:32px;">
                                <span class="icon" data-feather="sun"></span>
                            </label>
                        </div>
                    </li>

                {{-- Wishlist (live count via Livewire — single source of truth, event-driven) --}}
                <livewire:wishlist.wishlist-count />


                

                {{-- Cart (live count via Livewire — updates with no reload) --}}
                <livewire:cart.cart-count />
                {{-- (li wrapper is rendered by the component root) --}}

                {{-- User dropdown --}}
                <li class="nav-item dropdown feather-icon-wait">
                    <a class="nav-link px-2"
                       id="navbarUserDropdown"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown"
                       data-bs-auto-close="outside"
                       aria-haspopup="true"
                       aria-expanded="false"
                       aria-label="User account">
                        <span class="text-body-tertiary" data-feather="user" style="height:20px;width:20px;"></span>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border border-translucent mt-2"
                         style="min-width:260px;"
                         aria-labelledby="navbarUserDropdown">
                        <div class="card position-relative border-0">
                            @auth
                                <div class="card-body p-0">
                                    <div class="text-center pt-4 pb-3">
                                        <div class="avatar avatar-xl mb-2">
                                            <div class="avatar-name rounded-circle bg-primary-subtle">
                                                <span class="text-primary fw-bold fs-6">
                                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <h6 class="text-body-emphasis mb-0">{{ auth()->user()->name }}</h6>
                                        <p class="fs-10 text-body-tertiary mb-0">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>
                                <div class="overflow-auto scrollbar">
                                    <ul class="nav flex-column mb-2 pb-1">
                                        <li class="nav-item">
                                            <a class="nav-link px-3 d-flex align-items-center gap-2 {{ request()->routeIs('account.dashboard') ? 'text-primary' : '' }}"
                                               href="{{ route('account.dashboard') }}">
                                                <span data-feather="user" style="height:14px;width:14px;" class="text-body-tertiary flex-shrink-0"></span>
                                                My account
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link px-3 d-flex align-items-center gap-2 {{ request()->routeIs('account.orders') ? 'text-primary' : '' }}"
                                               href="{{ route('account.orders') }}">
                                                <span data-feather="shopping-bag" style="height:14px;width:14px;" class="text-body-tertiary flex-shrink-0"></span>
                                                My orders
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link px-3 d-flex align-items-center gap-2 {{ request()->routeIs('account.settings') ? 'text-primary' : '' }}"
                                               href="{{ route('account.settings') }}">
                                                <span data-feather="settings" style="height:14px;width:14px;" class="text-body-tertiary flex-shrink-0"></span>
                                                Settings
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-footer p-0 border-top border-translucent">
                                    <div class="px-3 py-3">
                                        <form action="{{ route('logout') }}" method="POST">
                                            @csrf
                                            <button class="btn btn-phoenix-secondary d-flex flex-center w-100 gap-2" type="submit">
                                                <span data-feather="log-out" style="height:14px;width:14px;"></span>
                                                Sign out
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="card-body p-0">
                                    <div class="text-center pt-4 pb-2">
                                        <div class="avatar avatar-xl mb-2">
                                            <div class="avatar-name rounded-circle bg-body-tertiary">
                                                <span class="text-body-tertiary">
                                                    <span data-feather="user" style="height:22px;width:22px;"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <h6 class="mt-1 text-body-emphasis mb-0">Welcome to gobuy</h6>
                                        <p class="fs-10 text-body-tertiary mb-0 px-3">Sign in to track orders &amp; checkout faster</p>
                                    </div>
                                </div>
                                <div class="card-footer p-0 border-top border-translucent">
                                    <div class="px-3 py-3 d-grid gap-2">
                                        <a class="btn btn-primary btn-sm" href="{{ route('login') }}">Sign in</a>
                                        <a class="btn btn-phoenix-secondary btn-sm" href="{{ route('register') }}">Create account</a>
                                    </div>
                                </div>
                            @endauth
                        </div>
                    </div>
                </li>

                </ul>
            </div>
        </nav>

        {{-- Mobile search bar (second row on small screens) --}}
        <div class="d-md-none pb-2">
            <x-search-box size="sm" />
        </div>
    </div>
</section>

<script>window.GoBuySearch = { trending: @json(($trendingSearches ?? collect())->values()) };</script>

{{-- ---- Secondary nav bar: mega-menu + page links ---- --}}
<nav class="bg-body-emphasis border-bottom border-translucent" aria-label="Category and page links">
    <div class="container-small">
        <div class="d-flex align-items-center gap-2" style="min-height:44px;">

            {{-- Categories mega-menu (always visible, desktop + mobile) --}}
            <div class="flex-shrink-0" id="megaMenuAnchor">
                <x-mega-menu :categories="$navCategories ?? []" />
            </div>

            {{-- Page links (scrollable on mobile) --}}
            <div class="flex-grow-1" style="overflow-x:auto; overflow-y:hidden; white-space:nowrap;">
                <ul class="navbar-nav flex-row align-items-center flex-nowrap justify-content-md-end" style="gap:0; padding-bottom: 2px;">
                    <li class="nav-item">
                        <a class="nav-link px-2 fs-9 text-nowrap {{ request()->routeIs('home') ? 'active fw-semibold' : '' }}"
                           href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-2 fs-9 text-nowrap {{ request()->routeIs('products.*') ? 'active fw-semibold' : '' }}"
                           href="{{ route('products.index') }}">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-2 fs-9 text-nowrap {{ request()->routeIs('wishlist.*') ? 'active fw-semibold' : '' }}"
                           href="{{ route('wishlist.index') }}">Wishlist</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-2 fs-9 text-nowrap {{ request()->routeIs('orders.track*') ? 'active fw-semibold' : '' }}"
                           href="{{ route('orders.track.form') }}">Track order</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-2 fs-9 text-nowrap {{ request()->routeIs('cart.*') ? 'active fw-semibold' : '' }}"
                           href="{{ route('cart.index') }}">Cart</a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</nav>

{{-- Mobile Categories Offcanvas --}}
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMegaMenu" aria-labelledby="mobileMegaMenuLabel">
    <div class="offcanvas-header border-bottom border-translucent">
        <h5 class="offcanvas-title" id="mobileMegaMenuLabel">All Categories</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column mb-0 w-100">
            <li class="nav-item w-100 border-bottom border-translucent">
                <a class="nav-link px-3 py-3 text-body-emphasis fw-bold" href="{{ route('products.index') }}">
                    <span class="fas fa-th-large me-2"></span> All Products
                </a>
            </li>
            @foreach($navCategories ?? [] as $category)
                <li class="nav-item w-100 border-bottom border-translucent">
                    @if($category->children->count() > 0)
                        <a class="nav-link px-3 py-3 d-flex align-items-center justify-content-between text-body-emphasis"
                           data-bs-toggle="collapse"
                           href="#collapseCat{{ $category->id }}"
                           role="button"
                           aria-expanded="false"
                           aria-controls="collapseCat{{ $category->id }}">
                            <span><span class="fas fa-tag me-2 opacity-50"></span> {{ $category->name }}</span>
                            <span class="fas fa-chevron-down fs-10"></span>
                        </a>
                        <div class="collapse bg-body-tertiary" id="collapseCat{{ $category->id }}">
                            <ul class="nav flex-column ms-4 py-2">
                                <li class="nav-item">
                                    <a class="nav-link py-2 fs-9 text-primary fw-semibold" href="{{ route('products.index', ['category' => $category->slug]) }}">
                                        View all {{ $category->name }} <span class="fas fa-arrow-right ms-1"></span>
                                    </a>
                                </li>
                                @foreach($category->children as $child)
                                    <li class="nav-item">
                                        <a class="nav-link py-2 fs-9 text-body" href="{{ route('products.index', ['category' => $child->slug]) }}">
                                            {{ $child->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <a class="nav-link px-3 py-3 text-body-emphasis" href="{{ route('products.index', ['category' => $category->slug]) }}">
                            <span class="fas fa-tag me-2 opacity-50"></span> {{ $category->name }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
