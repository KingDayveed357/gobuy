@props([
    'amount' => 0,
    'muted' => false,
    'strike' => false,
])

@php($formatted = money($amount))

<span {{ $attributes->merge(['class' => trim(($muted ? 'text-body-tertiary ' : '').($strike ? 'text-decoration-line-through' : ''))]) }}>{{ $formatted }}</span>
