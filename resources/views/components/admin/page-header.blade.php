@props(['title', 'subtitle' => null])

<div class="admin-page-header d-flex flex-wrap gap-3 justify-content-between align-items-start mb-4">
    <div class="min-w-0">
        @isset($breadcrumb)
            <nav class="mb-1" aria-label="breadcrumb">{{ $breadcrumb }}</nav>
        @endisset
        <h3 class="admin-page-title mb-0">{{ $title }}</h3>
        @if ($subtitle)
            <p class="text-body-tertiary fs-9 mb-0 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="d-flex flex-wrap gap-2 align-items-center">{{ $actions }}</div>
    @endisset
</div>
