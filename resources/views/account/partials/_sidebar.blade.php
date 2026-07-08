@php
    $isMobile = $isMobile ?? false;
    $navClass = $isMobile ? 'nav flex-column gap-1 p-3' : 'nav flex-column gap-1';
@endphp

<style>
    .account-nav-link {
        color: var(--phoenix-body-color);
        transition: all 0.2s ease-in-out;
        font-size: 0.875rem !important; /* larger fs-8 equivalent */
        padding: 0.65rem 0.85rem !important; /* comfortable touch target */
        border-radius: 0.375rem !important;
        display: flex;
        align-items: center;
    }
    .account-nav-link:hover {
        background-color: var(--phoenix-body-highlight-bg);
        color: var(--phoenix-primary) !important;
    }
    .account-nav-link.active {
        background-color: var(--phoenix-primary-subtle);
        color: var(--phoenix-primary) !important;
        font-weight: 600 !important;
    }
    .account-nav-icon {
        width: 20px;
        text-align: center;
        margin-right: 0.75rem;
        font-size: 0.95rem;
        color: var(--phoenix-secondary-color);
        transition: color 0.2s ease;
    }
    .account-nav-link:hover .account-nav-icon,
    .account-nav-link.active .account-nav-icon {
        color: var(--phoenix-primary);
    }
</style>

<div class="{{ $isMobile ? '' : 'card border-0 shadow-sm sticky-top' }}" style="top: 80px;">
    <div class="{{ $isMobile ? '' : 'card-body p-3' }}">
        @if(!$isMobile)
            <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-translucent">
                <div class="avatar avatar-xl me-3">
                    <div class="avatar-name rounded-circle bg-primary-subtle text-primary">
                        <span>{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                </div>
                <div>
                    <h6 class="mb-0 text-body-emphasis">{{ auth()->user()->name }}</h6>
                    <span class="fs-10 text-body-tertiary">{{ auth()->user()->email }}</span>
                </div>
            </div>
        @endif

        <nav class="{{ $navClass }}">
            <a href="{{ route('account.dashboard') }}" class="nav-link account-nav-link {{ request()->routeIs('account.dashboard') ? 'active' : '' }}">
                <span class="fas fa-home account-nav-icon"></span>
                <span>Dashboard</span>
            </a>
            
            <a href="{{ route('account.orders') }}" class="nav-link account-nav-link {{ request()->routeIs('account.orders*') ? 'active' : '' }}">
                <span class="fas fa-shopping-bag account-nav-icon"></span>
                <span>Orders</span>
            </a>
            
            <a href="{{ route('account.returns.index') }}" class="nav-link account-nav-link {{ request()->routeIs('account.returns*') ? 'active' : '' }}">
                <span class="fas fa-rotate-left account-nav-icon"></span>
                <span>Returns</span>
            </a>
            
            <a href="{{ route('account.wallet') }}" class="nav-link account-nav-link {{ request()->routeIs('account.wallet*') ? 'active' : '' }}">
                <span class="fas fa-wallet account-nav-icon"></span>
                <span>Store Credit</span>
            </a>
            
            <a href="{{ route('account.addresses.index') }}" class="nav-link account-nav-link {{ request()->routeIs('account.addresses*') ? 'active' : '' }}">
                <span class="fas fa-map-marker-alt account-nav-icon"></span>
                <span>Addresses</span>
            </a>
            
            <a href="{{ route('account.settings') }}" class="nav-link account-nav-link {{ request()->routeIs('account.settings*') ? 'active' : '' }}">
                <span class="fas fa-cog account-nav-icon"></span>
                <span>Settings</span>
            </a>

            <div class="mt-4 pt-3 border-top border-translucent {{ $isMobile ? 'px-3' : 'px-2' }}">
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link text-danger p-0 d-inline-flex align-items-center gap-2 text-decoration-none fw-semibold fs-8">
                        <span class="fas fa-sign-out-alt"></span>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>
</div>
