@extends('admin.layouts.app')

@section('title', 'Dispatch — Quintessential Mart admin')
@section('page-title', 'Dispatch')

@use('App\Modules\Logistics\Enums\ShipmentStatus')

@section('content')
    <x-admin.page-header title="Dispatch console" subtitle="{{ $shipments->total() }} shipment(s)">
        <x-slot:actions>
            <a href="{{ route('admin.logistics.index') }}" class="btn btn-phoenix-secondary"><span class="fas fa-gear me-2"></span>Zones &amp; pickups</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-3 d-flex flex-wrap gap-2">
        <a href="{{ route('admin.shipments.index') }}" class="btn btn-sm {{ $status === '' ? 'btn-primary' : 'btn-phoenix-secondary' }}">All</a>
        @foreach (ShipmentStatus::cases() as $case)
            <a href="{{ route('admin.shipments.index', ['status' => $case->value]) }}"
               class="btn btn-sm {{ $status === $case->value ? 'btn-primary' : 'btn-phoenix-secondary' }}">{{ $case->label() }}</a>
        @endforeach
    </div>

    <x-admin.table
        :cols="[
            ['label' => 'Order'],
            ['label' => 'Customer'],
            ['label' => 'Method'],
            ['label' => 'Destination'],
            ['label' => 'Waybill'],
            ['label' => 'Stage'],
            ['label' => 'Action', 'align' => 'end'],
        ]"
        :empty="$shipments->isEmpty()"
        empty-icon="fa-truck"
        empty-text="No shipments here."
    >
        @foreach ($shipments as $shipment)
            <tr>
                <td><a href="{{ route('admin.orders.show', $shipment->order) }}" class="fw-semibold text-body-emphasis text-decoration-none">{{ $shipment->order->order_number }}</a></td>
                <td>{{ $shipment->order->customer_name }}<br><span class="fs-10 text-body-tertiary">{{ $shipment->order->customer_phone }}</span></td>
                <td>{{ $shipment->methodLabel() }}</td>
                <td class="fs-9">
                    @if ($shipment->isPickup())
                        {{ $shipment->pickupLocation?->name ?? '—' }}
                    @else
                        {{ $shipment->order->city }}, {{ $shipment->order->state }}
                        @if ($shipment->zone)<br><span class="fs-10 text-body-tertiary">{{ $shipment->zone->name }}</span>@endif
                    @endif
                </td>
                <td class="fs-9">{{ $shipment->waybill ?? '—' }}</td>
                <td><span class="badge badge-phoenix badge-phoenix-info">{{ $shipment->status->label() }}</span></td>
                <td class="text-end">
                    @if ($shipment->status->next())
                        <form action="{{ route('admin.shipments.advance', $shipment) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-phoenix-primary">Mark {{ $shipment->status->next()->label() }}</button>
                        </form>
                    @else
                        <span class="badge badge-phoenix badge-phoenix-success">Delivered</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $shipments->links() }}</div>
@endsection
