@props(['value', 'label' => null])

@php
    $tones = [
        'paid' => 'success', 'success' => 'success', 'completed' => 'success',
        'delivered' => 'success', 'approved' => 'success', 'succeeded' => 'success',
        'pending' => 'warning', 'processing' => 'warning', 'unpaid' => 'warning',
        'shipped' => 'info', 'refunded' => 'info',
        'cancelled' => 'danger', 'failed' => 'danger', 'rejected' => 'danger',
        'active' => 'success', 'draft' => 'warning', 'archived' => 'secondary',
    ];
    $key = strtolower((string) ($value instanceof \BackedEnum ? $value->value : $value));
    $tone = $tones[$key] ?? 'secondary';
    $text = $label ?? ucfirst($key);
@endphp

<span {{ $attributes->merge(['class' => "badge badge-phoenix badge-phoenix-{$tone}"]) }}>{{ $text }}</span>
