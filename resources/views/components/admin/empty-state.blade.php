@props(['icon' => 'fa-inbox', 'title' => null, 'text' => 'Nothing to show yet.', 'compact' => false])

<div class="admin-empty text-center {{ $compact ? 'py-3' : 'py-6' }}">
    <span class="admin-empty-icon fas {{ $icon }}"></span>
    @if ($title)
        <h6 class="mt-3 mb-1 text-body-emphasis">{{ $title }}</h6>
    @endif
    <p class="text-body-tertiary fs-9 mb-0 mt-2">{{ $text }}</p>
    @isset($action)
        <div class="mt-3">{{ $action }}</div>
    @endisset
</div>
