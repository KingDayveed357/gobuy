@props(['id', 'type' => 'vertical'])

<div class="gb-wizard" id="{{ $id }}" data-gb-wizard="{{ $type }}">
    {{-- Form body — always order:1 on desktop, order:2 on mobile --}}
    <div class="gb-wizard-body">
        {{ $slot }}
        {{ $footer ?? '' }}
    </div>

    {{-- Step sidebar — order:2 on desktop, order:1 (top) on mobile --}}
    <ol class="gb-wizard-steps" role="tablist" aria-label="Settings steps">
        {{ $nav }}
    </ol>
</div>
