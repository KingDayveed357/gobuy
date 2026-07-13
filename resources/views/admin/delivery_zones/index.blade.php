@extends('admin.layouts.app')

@section('title', 'Delivery Zones — Quintessential Mart admin')
@section('page-title', 'Delivery Zones')

@section('content')
    <x-admin.page-header title="Delivery Zones">
        <x-slot:actions>
            <a href="{{ route('admin.delivery-zones.create') }}" class="btn btn-primary btn-sm"><span class="fas fa-plus me-2"></span>Add Zone</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs-9 mb-0">
                    <thead>
                        <tr>
                            <th>Zone Name</th>
                            <th>States Covered</th>
                            <th>Base Fee</th>
                            <th>Per Kg Fee</th>
                            <th>Free Over</th>
                            <th>Status</th>
                            <th>Sort Order</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($zones as $zone)
                            <tr>
                                <td class="align-middle fw-semibold text-body-emphasis">{{ $zone->name }}</td>
                                <td class="align-middle" style="max-width:250px">
                                    <div class="text-truncate" title="{{ $zone->states->pluck('state')->implode(', ') }}">
                                        {{ $zone->states->pluck('state')->implode(', ') ?: '—' }}
                                    </div>
                                </td>
                                <td class="align-middle">{{ money($zone->base_fee) }}</td>
                                <td class="align-middle">{{ money($zone->per_kg_fee) }}</td>
                                <td class="align-middle">{{ $zone->free_over_subtotal ? money($zone->free_over_subtotal) : '—' }}</td>
                                <td class="align-middle">
                                    @if($zone->is_active)
                                        <span class="badge badge-phoenix badge-phoenix-success">Active</span>
                                    @else
                                        <span class="badge badge-phoenix badge-phoenix-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="align-middle">{{ $zone->sort_order }}</td>
                                <td class="align-middle text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.delivery-zones.edit', $zone) }}" class="btn btn-sm btn-phoenix-secondary"><span class="fas fa-edit"></span></a>
                                        <form action="{{ route('admin.delivery-zones.destroy', $zone) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this delivery zone?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-phoenix-danger"><span class="fas fa-trash"></span></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-body-tertiary">No delivery zones found. Configure your first zone to enable calculated shipping.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
