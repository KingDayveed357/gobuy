@extends('admin.layouts.app')

@section('title', 'Logistics — gobuy admin')
@section('page-title', 'Logistics')

@section('content')
    <x-admin.page-header title="Zones &amp; pickup points" subtitle="Delivery pricing and pickup locations">
        <x-slot:actions>
            <a href="{{ route('admin.shipments.index') }}" class="btn btn-phoenix-secondary"><span class="fas fa-truck me-2"></span>Dispatch</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <x-admin.card title="Delivery zones" subtitle="Zone fees are configuration; states map to one zone each.">
                <div class="table-responsive">
                    <table class="table admin-table fs-9 mb-0">
                        <thead><tr><th>Zone</th><th>Covers</th><th class="text-end">Base</th><th class="text-end">Per kg</th><th class="text-end">Free over</th></tr></thead>
                        <tbody>
                            @foreach ($zones as $zone)
                                <tr>
                                    <td class="fw-semibold">{{ $zone->name }}</td>
                                    <td>{{ $zone->states->pluck('state')->implode(', ') ?: 'All other states' }}</td>
                                    <td class="text-end">{{ money($zone->base_fee) }}</td>
                                    <td class="text-end">{{ money($zone->per_kg_fee) }}</td>
                                    <td class="text-end">{{ $zone->free_over_subtotal ? money($zone->free_over_subtotal) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        <div class="col-12 col-xl-6">
            <x-admin.card title="Pickup locations" subtitle="Where customers can collect orders.">
                <x-slot:cardActions>
                    <button class="btn btn-phoenix-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addPickup"><span class="fas fa-plus me-1"></span>Add</button>
                </x-slot:cardActions>

                @if ($errors->any())
                    <div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif

                <div class="collapse mb-3" id="addPickup">
                    <form action="{{ route('admin.logistics.pickups.store') }}" method="POST" class="border border-translucent rounded-3 p-3">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-6"><input class="form-control form-control-sm" name="name" placeholder="Location name" required></div>
                            <div class="col-md-6"><input class="form-control form-control-sm" name="phone" placeholder="Phone"></div>
                            <div class="col-12"><input class="form-control form-control-sm" name="address" placeholder="Address" required></div>
                            <div class="col-md-6"><input class="form-control form-control-sm" name="city" placeholder="City" required></div>
                            <div class="col-md-6"><input class="form-control form-control-sm" name="state" placeholder="State" required></div>
                            <div class="col-12"><input class="form-control form-control-sm" name="opening_hours" placeholder="Opening hours (e.g. Mon–Sat, 9am–6pm)"></div>
                        </div>
                        <button class="btn btn-sm btn-primary mt-3">Add pickup point</button>
                    </form>
                </div>

                @forelse ($pickups as $pickup)
                    <div class="d-flex flex-between-center border-bottom border-translucent py-2">
                        <div>
                            <p class="fw-semibold mb-0 fs-9">{{ $pickup->name }} @unless($pickup->is_active)<span class="badge badge-phoenix badge-phoenix-secondary ms-1">Inactive</span>@endunless</p>
                            <p class="fs-10 text-body-tertiary mb-0">{{ $pickup->formatted() }}{{ $pickup->opening_hours ? ' · '.$pickup->opening_hours : '' }}</p>
                        </div>
                        <button type="button" class="btn btn-sm text-danger" data-bs-toggle="modal" data-bs-target="#actionModal" data-action="{{ route('admin.logistics.pickups.destroy', $pickup) }}" data-method="DELETE" data-title="Remove Pickup Location" data-message="Are you sure you want to remove this pickup location?" data-confirm-text="Yes, remove it" data-variant="danger"><span class="fas fa-trash"></span></button>
                    </div>
                @empty
                    <p class="text-body-tertiary fs-9 mb-0">No pickup locations yet.</p>
                @endforelse
            </x-admin.card>
        </div>
    </div>
@endsection
