@props([
    'type' => 'block',   // block | text | table | card | stat | list | media
    'lines' => 3,        // text: number of shimmer lines
    'rows' => 5,         // table/list: number of rows
    'cols' => 4,         // table: number of columns
    'width' => null,     // block: e.g. '60%', '3rem'
    'height' => null,    // block: e.g. '1rem', '120px'
    'circle' => false,   // block: render as a circle
])

@php
    $style = collect([
        $width ? "width: {$width}" : null,
        $height ? "height: {$height}" : null,
    ])->filter()->implode('; ');
@endphp

{{--
    Premium skeleton loader (Sprint item #3). Theme-aware shimmer; use it for any
    "loading should never feel empty" surface — as a Livewire lazy `placeholder()`,
    inside a `wire:loading` slot, or as a static waiting state.

    Examples (drop the leading backslash):
        \x-admin.skeleton type="text" :lines="3"
        \x-admin.skeleton type="table" :rows="6" :cols="5"
        \x-admin.skeleton type="card"
        \x-admin.skeleton type="stat" :rows="4"        (4 stat tiles)
        \x-admin.skeleton width="40px" height="40px" circle
--}}
<div {{ $attributes->merge(['class' => 'gb-skel-root']) }} aria-hidden="true" role="presentation">
    @switch($type)
        @case('text')
            <div class="gb-skel-lines">
                @for ($i = 0; $i < (int) $lines; $i++)
                    <span class="gb-skel gb-skel-line" @style(['width: '.[92, 100, 78, 85, 64][$i % 5].'%'])></span>
                @endfor
            </div>
            @break

        @case('table')
            <div class="gb-skel-table">
                @for ($r = 0; $r < (int) $rows; $r++)
                    <div class="gb-skel-tr">
                        @for ($c = 0; $c < (int) $cols; $c++)
                            <span class="gb-skel gb-skel-cell" @style(['width: '.($c === 0 ? 40 : ($c === $cols - 1 ? 60 : 80)).'%'])></span>
                        @endfor
                    </div>
                @endfor
            </div>
            @break

        @case('list')
            <div class="gb-skel-list">
                @for ($r = 0; $r < (int) $rows; $r++)
                    <div class="gb-skel-li">
                        <span class="gb-skel gb-skel-avatar"></span>
                        <div class="gb-skel-li-body">
                            <span class="gb-skel gb-skel-line" style="width: 55%"></span>
                            <span class="gb-skel gb-skel-line gb-skel-line-sm" style="width: 35%"></span>
                        </div>
                        <span class="gb-skel gb-skel-line" style="width: 12%"></span>
                    </div>
                @endfor
            </div>
            @break

        @case('card')
            <div class="gb-skel-card">
                <span class="gb-skel gb-skel-media"></span>
                <span class="gb-skel gb-skel-line" style="width: 80%"></span>
                <span class="gb-skel gb-skel-line gb-skel-line-sm" style="width: 45%"></span>
                <span class="gb-skel gb-skel-line" style="width: 30%; margin-top: 0.5rem"></span>
            </div>
            @break

        @case('stat')
            <div class="gb-skel-stats">
                @for ($r = 0; $r < (int) $rows; $r++)
                    <div class="gb-skel-stat">
                        <span class="gb-skel gb-skel-line gb-skel-line-sm" style="width: 50%"></span>
                        <span class="gb-skel gb-skel-line gb-skel-stat-value" style="width: 70%"></span>
                        <span class="gb-skel gb-skel-line gb-skel-line-sm" style="width: 40%"></span>
                    </div>
                @endfor
            </div>
            @break

        @case('media')
            <span class="gb-skel gb-skel-media" @style([$style])></span>
            @break

        @default
            <span class="gb-skel {{ $circle ? 'gb-skel-circle' : '' }}" @style([$style ?: 'height: 1rem'])></span>
    @endswitch
</div>

@once
    @push('styles')
        <link href="{{ asset('theme/css/skeleton.css') }}" rel="stylesheet">
    @endpush
@endonce
