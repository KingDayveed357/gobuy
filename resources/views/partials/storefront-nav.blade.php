<section class="py-0">
    <div class="container-small">
        <div class="ecommerce-topbar  border-translucent">
            <nav class="navbar navbar-expand-lg navbar-light px-0 py-3">
                <div class="row gx-0 gy-2 w-100 flex-between-center">
                    <div class="col-auto">
                        <a class="text-decoration-none" href="{{ route('home') }}">
                            <div class="d-flex align-items-center">
                                <h4 class="logo-text text-primary mb-0">gobuy</h4>
                            </div>
                        </a>
                    </div>
                    <div class="col-auto order-md-1">
                        <ul class="navbar-nav navbar-nav-icons flex-row me-n2">
                            <li class="nav-item d-flex align-items-center">
                                <div class="theme-control-toggle feather-icon-wait px-2">
                                    <input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggle">
                                    <label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Switch to dark theme" style="height:32px;width:32px;"><span class="icon" data-feather="moon"></span></label>
                                    <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Switch to light theme" style="height:32px;width:32px;"><span class="icon" data-feather="sun"></span></label>
                                </div>
                            </li>
                            <li class="nav-item feather-icon-wait" style="height: 40px;">
                                <a class="nav-link px-2 {{ ($cartCount ?? 0) > 0 ? 'icon-indicator icon-indicator-primary' : '' }}" href="{{ route('cart.index') }}" role="button">
                                    <span class="text-body-tertiary" data-feather="shopping-cart" style="height:20px;width:20px;"></span>
                                    @if (($cartCount ?? 0) > 0)
                                        <span class="icon-indicator-number">{{ $cartCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item dropdown feather-icon-wait" style="height: 40px;">
                                <a class="nav-link px-2" id="navbarUser" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                                    <span class="text-body-tertiary" data-feather="user" style="height:20px;width:20px;"></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border mt-2" aria-labelledby="navbarUser">
                                    <div class="card position-relative border-0">
                                        @auth
                                            <div class="card-body p-0">
                                                <div class="text-center pt-4 pb-3">
                                                    <div class="avatar avatar-xl">
                                                        <div class="avatar-name rounded-circle bg-primary-subtle"><span class="text-primary fw-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span></div>
                                                    </div>
                                                    <h6 class="mt-2 text-body-emphasis mb-0">{{ auth()->user()->name }}</h6>
                                                    <p class="fs-9 text-body-tertiary mb-0">{{ auth()->user()->email }}</p>
                                                </div>
                                            </div>
                                            <div class="overflow-auto scrollbar">
                                                <ul class="nav d-flex flex-column mb-2 pb-1">
                                                    <li class="nav-item"><a class="nav-link px-3 d-block" href="{{ route('account.dashboard') }}"><span class="me-2 text-body align-bottom" data-feather="user"></span>My account</a></li>
                                                    <li class="nav-item"><a class="nav-link px-3 d-block" href="{{ route('account.orders') }}"><span class="me-2 text-body align-bottom" data-feather="shopping-bag"></span>My orders</a></li>
                                                </ul>
                                            </div>
                                            <div class="card-footer p-0 border-top border-translucent">
                                                <div class="px-3 py-3">
                                                    <form action="{{ route('logout') }}" method="POST">
                                                        @csrf
                                                        <button class="btn btn-phoenix-secondary d-flex flex-center w-100" type="submit"><span class="me-2" data-feather="log-out"></span>Sign out</button>
                                                    </form>
                                                </div>
                                            </div>
                                        @else
                                            <div class="card-body p-0">
                                                <div class="text-center pt-4 pb-2">
                                                    <div class="avatar avatar-xl">
                                                        <div class="avatar-name rounded-circle bg-primary-subtle"><span class="text-primary"><span data-feather="user"></span></span></div>
                                                    </div>
                                                    <h6 class="mt-2 text-body-emphasis mb-0">Welcome to gobuy</h6>
                                                    <p class="fs-9 text-body-tertiary mb-0">Sign in to track orders &amp; checkout faster</p>
                                                </div>
                                            </div>
                                            <div class="card-footer p-0 border-top border-translucent">
                                                <div class="px-3 py-3 d-grid gap-2">
                                                    <a class="btn btn-primary" href="{{ route('login') }}">Sign in</a>
                                                    <a class="btn btn-phoenix-secondary" href="{{ route('register') }}">Create account</a>
                                                </div>
                                            </div>
                                        @endauth
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="col-12 col-md-6">
                        <form class="search-box w-100" action="{{ route('products.index') }}" method="GET">
                            <input class="form-control search-input search form-control-sm" type="search" name="q"
                                   value="{{ request('q') }}" placeholder="Search products" aria-label="Search">
                            <span class="fas fa-search search-box-icon"></span>
                        </form>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</section>

<nav class="navbar-responsive-navitems navbar-expand navbar-light bg-body-emphasis justify-content-between">
    <div class="container-small d-flex flex-between-center">
        <div class="dropdown feather-icon-wait">
            <button class="btn text-body ps-0 pe-5 text-nowrap dropdown-toggle dropdown-caret-none" data-bs-toggle="dropdown">
                <span class="fas fa-bars me-2"></span>Categories
            </button>
            <div class="dropdown-menu border border-translucent py-2">
                <a class="dropdown-item" href="{{ route('products.index') }}">All products</a>
                @foreach ($navCategories ?? [] as $category)
                    <a class="dropdown-item" href="{{ route('products.index', ['category' => $category->slug]) }}">{{ $category->name }}</a>
                @endforeach
            </div>
        </div>
        <ul class="navbar-nav justify-content-end align-items-center">
            <li class="nav-item"><a class="nav-link ps-0 {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">Products</a></li>
            <li class="nav-item"><a class="nav-link {{ request()->routeIs('orders.track*') ? 'active' : '' }}" href="{{ route('orders.track.form') }}">Track order</a></li>
            <li class="nav-item"><a class="nav-link pe-0 {{ request()->routeIs('cart.*') ? 'active' : '' }}" href="{{ route('cart.index') }}">Cart</a></li>
        </ul>
    </div>
</nav>
