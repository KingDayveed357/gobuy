@props(['item'])

{{-- Template parity: link renders as a bare nav-item-wrapper div (no wrapping <li>).
     The parent <li class="nav-item"> is managed by navbar-vertical.blade.php. --}}
<div class="nav-item-wrapper">
    <a @class([
            'nav-link label-1',
            'active' => $item['active'] ?? false,
        ])
       href="{{ route($item['route']) }}">
        <div class="d-flex align-items-center">
            @if (! empty($item['icon']))
                <span class="nav-link-icon"><span data-feather="{{ $item['icon'] }}"></span></span>
            @endif
            <span class="nav-link-text-wrapper">
                <span class="nav-link-text">{{ $item['label'] }}</span>
            </span>
        </div>
    </a>
</div>
