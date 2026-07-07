@props([
    'label',        // string — e.g. "Bill To", "Ship To", "Sold By"
    'name'  => null, // string|null — primary name line
    'lines' => [],   // array<string> — additional address lines
    'extra' => null, // optional slot for extra detail (phone, email, etc.)
])

{{--
    Address Block Component
    ───────────────────────
    Renders a labeled address box used for bill-to, ship-to, sold-by,
    return address, or any named address block. Uses a bordered card
    style that is legible and clearly separates addresses on the page.

    Usage:
        <x-document.address-block
            label="Ship To"
            name="John Doe"
            :lines="['123 Main St', 'Port Harcourt, Rivers State', 'Nigeria']"
        />
--}}
<div class="doc-address">
    <div class="doc-address__label">{{ $label }}</div>

    @if ($name)
        <div class="doc-address__name">{{ $name }}</div>
    @endif

    @if (!empty($lines))
        <div class="doc-address__detail">
            @foreach ($lines as $line)
                @if ($line)
                    {{ $line }}@if (!$loop->last)<br>@endif
                @endif
            @endforeach
        </div>
    @endif

    @if ($slot->isNotEmpty())
        <div class="doc-address__detail" style="margin-top:4px;">
            {{ $slot }}
        </div>
    @endif
</div>
