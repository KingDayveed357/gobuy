@props(['group'])

{{-- Template parity: group renders as a bare nav-item-wrapper div (no wrapping <li>).
     The parent <li class="nav-item"> is managed by navbar-vertical.blade.php so that
     multiple groups under one section share the same <li>, exactly matching the Phoenix
     template structure required for the collapsed-state hover flyout. --}}
<div class="nav-item-wrapper">
    <a @class([
            'nav-link dropdown-indicator label-1',
            'active' => $group['active'] ?? false,
        ])
       href="#{{ $group['id'] }}"
       role="button"
       data-bs-toggle="collapse"
       aria-expanded="{{ ($group['expanded'] ?? false) ? 'true' : 'false' }}"
       aria-controls="{{ $group['id'] }}">
        <div class="d-flex align-items-center">
            <div class="dropdown-indicator-icon-wrapper">
                <span class="fas fa-caret-right dropdown-indicator-icon"></span>
            </div>
            @if (! empty($group['icon']))
                <span class="nav-link-icon"><span data-feather="{{ $group['icon'] }}"></span></span>
            @endif
            <span class="nav-link-text">{{ $group['label'] }}</span>
        </div>
    </a>
    <div class="parent-wrapper label-1">
        <ul @class([
                'nav collapse parent',
                'show' => $group['expanded'] ?? false,
            ])
            data-bs-parent="#navbarVerticalCollapse"
            id="{{ $group['id'] }}">
            <li class="collapsed-nav-item-title d-none">{{ $group['label'] }}</li>
            @foreach ($group['items'] as $item)
                <li class="nav-item">
                    <a @class(['nav-link', 'active' => $item['active'] ?? false])
                       href="{{ route($item['route']) }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-text">{{ $item['label'] }}</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
