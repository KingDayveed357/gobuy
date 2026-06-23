<li class="nav-item">
    {{-- Font Awesome (CSS) — survives Livewire morphs, unlike Feather. --}}
    <a class="nav-link px-2 position-relative" href="{{ route('wishlist.index') }}" aria-label="Wishlist">
        <span class="far fa-heart text-body-tertiary" style="font-size:18px;"></span>
        <span class="wishlist-badge {{ $count > 0 ? '' : 'd-none' }}">{{ $count }}</span>
    </a>
</li>
