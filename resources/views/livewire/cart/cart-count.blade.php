<li class="nav-item">
    {{-- Font Awesome (pure CSS) — survives Livewire DOM morphs, unlike Feather
         icons which only run feather.replace() once on initial page load. --}}
    <a class="nav-link px-2 position-relative {{ $count > 0 ? 'icon-indicator icon-indicator-primary' : '' }}"
       href="{{ route('cart.index') }}"
       aria-label="Shopping cart">
        <span class="fas fa-cart-shopping text-body-tertiary" style="font-size:18px;"></span>
        @if ($count > 0)
            <span class="icon-indicator-number">{{ $count }}</span>
        @endif
    </a>
</li>
