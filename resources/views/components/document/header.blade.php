@props([
    'document', // DocumentInterface
    'branding', // array from DocumentBrandingService
])

{{--
    Document Header Component
    ─────────────────────────
    Renders the branded top section of every document:
    left  → store logo (if configured) or store name + contact details
    right → document type label + reference + issue date

    This component is the single source of truth for document identity.
    Update it here and every document type immediately benefits.
--}}
<header class="doc-header">
    {{-- Left: Store brand --}}
    <div class="doc-header__brand">
        @if ($branding['logo_url'])
            <img
                src="{{ $branding['logo_url'] }}"
                alt="{{ $branding['store_name'] }}"
                class="doc-header__logo"
            >
        @else
            <div class="doc-header__store-name">{{ $branding['store_name'] }}</div>
        @endif

        <div class="doc-header__store-meta">
            @if ($branding['address'])
                {{ $branding['address'] }}<br>
            @endif
            @if ($branding['store_email'])
                {{ $branding['store_email'] }}
                @if ($branding['store_phone']) · @endif
            @endif
            @if ($branding['store_phone'])
                {{ $branding['store_phone'] }}
            @endif
            @if ($branding['vat_number'])
                <br>VAT Reg: {{ $branding['vat_number'] }}
            @endif
            @if ($branding['tax_id'])
                <br>TIN: {{ $branding['tax_id'] }}
            @endif
        </div>
    </div>

    {{-- Right: Document identity --}}
    <div class="doc-header__doc-info">
        <div class="doc-header__doc-type">{{ $document->getDocumentType() }}</div>
        <div class="doc-header__reference">{{ $document->getReference() }}</div>
        <div class="doc-header__date">
            Issued: {{ now()->format('M j, Y') }}<br>
            @if ($slot->isNotEmpty())
                {{ $slot }}
            @endif
        </div>
    </div>
</header>
