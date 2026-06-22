@props(['id', 'icon', 'title', 'subtitle' => '', 'step', 'active' => false])

<li class="gb-step {{ $active ? 'is-active' : '' }}"
    role="tab"
    data-gb-step="{{ $step }}"
    data-gb-target="{{ $id }}"
    tabindex="{{ $active ? '0' : '-1' }}"
    aria-selected="{{ $active ? 'true' : 'false' }}">
    <div class="gb-step-circle">
        <i class="{{ $icon }}"></i>
    </div>
    <div class="gb-step-label">
        <span class="gb-step-title">{{ $title }}</span>
        @if($subtitle)
            <span class="gb-step-subtitle">{{ $subtitle }}</span>
        @endif
    </div>
</li>
