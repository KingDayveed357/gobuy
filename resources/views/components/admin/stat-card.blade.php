@props(['label', 'value', 'icon' => 'fa-chart-simple', 'tone' => 'primary', 'hint' => null])

<div class="admin-stat">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <span class="text-body-tertiary fs-9 fw-semibold text-uppercase">{{ $label }}</span>
        <span class="admin-stat-icon bg-{{ $tone }}-subtle text-{{ $tone }}"><span class="fas {{ $icon }}"></span></span>
    </div>
    <div class="admin-stat-value">{{ $value }}</div>
    @if ($hint)
        <p class="fs-9 text-body-tertiary mb-0 mt-1">{{ $hint }}</p>
    @endif
</div>
