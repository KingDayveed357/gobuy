@props([
    'size' => 40,       // rendered height in px
    'label' => 'Quintessential Mart',
    'sub' => null,      // accepted for backwards-compat; intentionally not rendered
    'href' => route('home'),
])

{{-- Quintessential Mart logo. Uses the generated horizontal lockups, swapping
     light/dark by the Phoenix data-bs-theme flag. The link lives inside the
     component so navbar/footer markup stays clean and aligned. --}}
@if ($href)
<a href="{{ $href }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'gb-brand d-inline-flex align-items-center']) }}>
@else
<span {{ $attributes->merge(['class' => 'gb-brand d-inline-flex align-items-center']) }}>
@endif
    <img src="{{ asset('branding/logos/icon.svg') }}" alt="" aria-hidden="true"
         class="gb-brand-lockup gb-brand-icon" style="height: {{ $size }}px;" width="64" height="64">
    <img src="{{ asset('branding/logos/logo-light.svg') }}" alt="{{ $label }}"
         class="gb-brand-lockup gb-brand--light" style="height: {{ $size }}px;" width="440" height="64">
    <img src="{{ asset('branding/logos/logo-dark.svg') }}" alt="{{ $label }}"
         class="gb-brand-lockup gb-brand--dark" style="height: {{ $size }}px;" width="440" height="64">
@if ($href)
</a>
@else
</span>
@endif

