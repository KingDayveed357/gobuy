@props([
    'title' => null,
    'subtitle' => null,
    'eyebrow' => null,
    'flush' => false,
    'bodyClass' => '',
])

@php
    $classes = 'card admin-card';
    $hasHeightClass = false;
    if ($attributes->has('class')) {
        $userClasses = explode(' ', $attributes->get('class'));
        foreach ($userClasses as $c) {
            if (str_starts_with($c, 'h-')) {
                $hasHeightClass = true;
                break;
            }
        }
    }
    if (!$hasHeightClass) {
        $classes .= ' h-100';
    }
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if ($title || $subtitle || $eyebrow || isset($cardActions))
        <div class="card-header admin-card-header d-flex flex-wrap gap-3 justify-content-between align-items-start">
            <div class="min-w-0">
                @if ($eyebrow)
                    <div class="admin-card-eyebrow mb-1">{{ $eyebrow }}</div>
                @endif
                @if ($title)
                    <h5 class="mb-0">{{ $title }}</h5>
                @endif
                @if ($subtitle)
                    <p class="admin-card-subtitle mb-0 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($cardActions)
                <div class="d-flex flex-wrap gap-2 align-items-center">{{ $cardActions }}</div>
            @endisset
        </div>
    @endif
    <div class="card-body {{ $flush ? 'p-0' : '' }} {{ $bodyClass }}">{{ $slot }}</div>
</div>
