@extends('admin.layouts.app')

@section('title', 'Return Centres — gobuy admin')
@section('page-title', 'Return Centres')

@section('content')
    <x-admin.page-header title="Return Centres">
        <x-slot:actions>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createReturnCentreModal">
                <span class="fas fa-plus me-2"></span>Add Centre
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="modal fade" id="createReturnCentreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.return-centres.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Return Centre</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label" for="name">Centre Name</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. Lagos Mainland Hub">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="address">Dropoff Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required placeholder="Full shipping address for returns"></textarea>
                            <div class="form-text">This will be printed on the return label instructions.</div>
                        </div>
                        <div class="d-flex flex-column gap-2 mt-4">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Centre is Active</label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                                <label class="form-check-label" for="is_default">Make Default Return Centre</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm"><span class="fas fa-save me-2"></span>Save Centre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm fs-9 mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Dropoff Address</th>
                            <th>Default</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($centres as $centre)
                            <tr>
                                <td class="align-middle fw-semibold text-body-emphasis">{{ $centre->name }}</td>
                                <td class="align-middle">{{ $centre->address }}</td>
                                <td class="align-middle">
                                    @if($centre->is_default)
                                        <span class="badge badge-phoenix badge-phoenix-info">Default</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($centre->is_active)
                                        <span class="badge badge-phoenix badge-phoenix-success">Active</span>
                                    @else
                                        <span class="badge badge-phoenix badge-phoenix-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="align-middle text-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-phoenix-secondary" data-bs-toggle="modal" data-bs-target="#editReturnCentreModal-{{ $centre->id }}">
                                            <span class="fas fa-edit"></span>
                                        </button>
                                        <form action="{{ route('admin.return-centres.destroy', $centre) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this return centre?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-phoenix-danger"><span class="fas fa-trash"></span></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-body-tertiary">No return centres found. Customers will be directed to the default address in settings.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach($centres as $centre)
        <div class="modal fade" id="editReturnCentreModal-{{ $centre->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.return-centres.update', $centre) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Return Centre</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-start">
                            <div class="mb-3">
                                <label class="form-label" for="edit_name_{{ $centre->id }}">Centre Name</label>
                                <input type="text" class="form-control" id="edit_name_{{ $centre->id }}" name="name" value="{{ old('name', $centre->name) }}" required placeholder="e.g. Lagos Mainland Hub">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="edit_address_{{ $centre->id }}">Dropoff Address</label>
                                <textarea class="form-control" id="edit_address_{{ $centre->id }}" name="address" rows="3" required placeholder="Full shipping address for returns">{{ old('address', $centre->address) }}</textarea>
                                <div class="form-text">This will be printed on the return label instructions.</div>
                            </div>
                            <div class="d-flex flex-column gap-2 mt-4">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active_{{ $centre->id }}" name="is_active" value="1" @checked(old('is_active', $centre->is_active))>
                                    <label class="form-check-label" for="edit_is_active_{{ $centre->id }}">Centre is Active</label>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="edit_is_default_{{ $centre->id }}" name="is_default" value="1" @checked(old('is_default', $centre->is_default))>
                                    <label class="form-check-label" for="edit_is_default_{{ $centre->id }}">Make Default Return Centre</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm"><span class="fas fa-save me-2"></span>Update Centre</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
