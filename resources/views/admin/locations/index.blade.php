@extends('admin.layouts.app')

@section('title', 'Locations — Quintessential Mart admin')
@section('page-title', 'Locations')

@section('content')
    <nav class="mb-2" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.logistics.index') }}">Logistics Hub</a></li>
            <li class="breadcrumb-item active" aria-current="page">Locations</li>
        </ol>
    </nav>
    <x-admin.page-header title="Locations" subtitle="Manage physical locations such as warehouses, pickup points, and return centres.">
        <x-slot:actions>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createLocationModal"><span class="fas fa-plus me-2"></span>Add Location</button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="alert alert-subtle-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table admin-table fs-9 mb-0">
                    <thead>
                        <tr>
                            <th>Location Details</th>
                            <th>Capabilities</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($locations as $location)
                            <tr>
                                <td>
                                    <h6 class="mb-1 text-body-emphasis">{{ $location->name }} {!! $location->is_default_return ? '<span class="badge badge-phoenix badge-phoenix-primary ms-2"><span class="fas fa-star me-1"></span>Default Return</span>' : '' !!}</h6>
                                    <p class="mb-0 text-body-tertiary fs-10">{{ $location->formatted() }}</p>
                                    @if ($location->phone || $location->opening_hours)
                                        <p class="mb-0 text-body-tertiary fs-10 mt-1">
                                            @if ($location->phone)<span class="fas fa-phone me-1"></span>{{ $location->phone }}@endif
                                            @if ($location->opening_hours)<span class="fas fa-clock ms-2 me-1"></span>{{ $location->opening_hours }}@endif
                                        </p>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if ($location->is_pickup)
                                            <span class="badge badge-phoenix badge-phoenix-info"><span class="fas fa-box-open me-1"></span>Pickup Point</span>
                                        @endif
                                        @if ($location->is_return)
                                            <span class="badge badge-phoenix badge-phoenix-warning"><span class="fas fa-rotate-left me-1"></span>Return Centre</span>
                                        @endif
                                        @if (! $location->is_pickup && ! $location->is_return)
                                            <span class="text-body-tertiary fs-10">No capabilities assigned</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($location->is_active)
                                        <span class="badge badge-phoenix badge-phoenix-success">Active</span>
                                    @else
                                        <span class="badge badge-phoenix badge-phoenix-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-phoenix-secondary" data-bs-toggle="modal" data-bs-target="#editLocationModal{{ $location->id }}"><span class="fas fa-edit"></span></button>
                                        <button class="btn btn-sm btn-phoenix-danger" data-bs-toggle="modal" data-bs-target="#actionModal" data-action="{{ route('admin.locations.destroy', $location) }}" data-method="DELETE" data-title="Delete Location" data-message="Are you sure you want to delete {{ $location->name }}? This cannot be undone." data-confirm-text="Delete"><span class="fas fa-trash"></span></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editLocationModal{{ $location->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <form action="{{ route('admin.locations.update', $location) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Location</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Location Name</label>
                                                        <input class="form-control" name="name" value="{{ $location->name }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Phone (Optional)</label>
                                                        <input class="form-control" name="phone" value="{{ $location->phone }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Address</label>
                                                        <input class="form-control" name="address" value="{{ $location->address }}" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">City (Optional)</label>
                                                        <input class="form-control" name="city" value="{{ $location->city }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">State (Optional)</label>
                                                        <input class="form-control" name="state" value="{{ $location->state }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Opening Hours (Optional)</label>
                                                        <input class="form-control" name="opening_hours" value="{{ $location->opening_hours }}">
                                                    </div>
                                                    
                                                    <div class="col-12 mt-4">
                                                        <h6 class="mb-3">Capabilities & Settings</h6>
                                                        <input type="hidden" name="is_pickup" value="0">
                                                        <input type="hidden" name="is_return" value="0">
                                                        <input type="hidden" name="is_default_return" value="0">
                                                        <input type="hidden" name="is_active" value="0">
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox" name="is_pickup" value="1" id="pickup{{ $location->id }}" @checked($location->is_pickup)>
                                                            <label class="form-check-label" for="pickup{{ $location->id }}">Act as Pickup Point</label>
                                                        </div>
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox" name="is_return" value="1" id="return{{ $location->id }}" @checked($location->is_return)>
                                                            <label class="form-check-label" for="return{{ $location->id }}">Act as Return Centre</label>
                                                        </div>
                                                        <div class="form-check form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox" name="is_default_return" value="1" id="default{{ $location->id }}" @checked($location->is_default_return)>
                                                            <label class="form-check-label" for="default{{ $location->id }}">Set as Default Return Centre (will remove default from others)</label>
                                                        </div>
                                                        <hr class="my-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $location->id }}" @checked($location->is_active)>
                                                            <label class="form-check-label" for="active{{ $location->id }}">Location Active</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-body-tertiary py-4">No locations added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.locations.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Location</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Location Name</label>
                                <input class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone (Optional)</label>
                                <input class="form-control" name="phone">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <input class="form-control" name="address" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City (Optional)</label>
                                <input class="form-control" name="city">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">State (Optional)</label>
                                <input class="form-control" name="state">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Opening Hours (Optional)</label>
                                <input class="form-control" name="opening_hours" placeholder="e.g. Mon–Sat, 9am–6pm">
                            </div>
                            
                            <div class="col-12 mt-4">
                                <h6 class="mb-3">Capabilities & Settings</h6>
                                <input type="hidden" name="is_pickup" value="0">
                                <input type="hidden" name="is_return" value="0">
                                <input type="hidden" name="is_default_return" value="0">
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_pickup" value="1" id="createPickup" checked>
                                    <label class="form-check-label" for="createPickup">Act as Pickup Point</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_return" value="1" id="createReturn">
                                    <label class="form-check-label" for="createReturn">Act as Return Centre</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="is_default_return" value="1" id="createDefault">
                                    <label class="form-check-label" for="createDefault">Set as Default Return Centre</label>
                                </div>
                                <hr class="my-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createActive" checked>
                                    <label class="form-check-label" for="createActive">Location Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Location</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
