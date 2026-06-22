@props(['shipment'])

@use('App\Modules\Logistics\Enums\ShipmentStatus')

@php($current = $shipment->status->position())

<div class="d-flex flex-column gap-0">
    @foreach (ShipmentStatus::timeline() as $i => $stage)
        @php($done = $i <= $current)
        <div class="d-flex align-items-start gap-3">
            <div class="d-flex flex-column align-items-center">
                <span class="d-flex flex-center rounded-circle {{ $done ? 'bg-primary text-white' : 'bg-body-secondary text-body-tertiary' }}" style="width: 1.75rem; height: 1.75rem;">
                    <span class="fas {{ $i < $current ? 'fa-check' : 'fa-circle' }} fs-10"></span>
                </span>
                @unless ($loop->last)
                    <span style="width: 2px; height: 1.75rem; background: {{ $i < $current ? 'var(--phoenix-primary)' : 'var(--phoenix-border-color-translucent)' }};"></span>
                @endunless
            </div>
            <div class="pb-3">
                <p class="mb-0 fw-semibold {{ $done ? 'text-body-emphasis' : 'text-body-tertiary' }}">{{ $stage->label() }}</p>
                @if ($stage === ShipmentStatus::Dispatched && $shipment->dispatched_at)
                    <p class="fs-10 text-body-tertiary mb-0">{{ $shipment->dispatched_at->format('M j, Y g:i A') }}@if ($shipment->waybill) · Waybill {{ $shipment->waybill }}@endif</p>
                @elseif ($stage === ShipmentStatus::Delivered && $shipment->delivered_at)
                    <p class="fs-10 text-body-tertiary mb-0">{{ $shipment->delivered_at->format('M j, Y g:i A') }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>
