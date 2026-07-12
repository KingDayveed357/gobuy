@extends('admin.layouts.app')

@section('title', 'Stock locations — gobuy admin')
@section('page-title', 'Stock locations')

@section('content')
    <x-admin.page-header title="Stock locations" subtitle="Where your stock physically sits — shop, home storage, warehouse. Every location's on-hand rolls up to each product's total.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary btn-sm" id="btnNewLocation"><span class="fas fa-plus me-1"></span>Add location</button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <x-admin.card flush>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead><tr><th>Location</th><th>Type</th><th class="text-end">SKUs</th><th class="text-end">Units on hand</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @foreach ($locations as $location)
                        <tr>
                            <td>
                                <a href="{{ route('admin.stock-locations.show', $location) }}" class="fw-semibold text-body-emphasis text-decoration-none">{{ $location->name }}</a>
                                @if ($location->is_default)<span class="badge badge-phoenix badge-phoenix-info ms-1">Default</span>@endif
                                <br><span class="fs-10 text-body-tertiary">{{ $location->code }}</span>
                            </td>
                            <td class="fs-9 text-capitalize">{{ $location->type ?: '—' }}</td>
                            <td class="text-end fs-9">{{ (int) $location->sku_count }}</td>
                            <td class="text-end fw-semibold">{{ number_format((int) $location->units_on_hand) }}</td>
                            <td>
                                @if ($location->is_active)<span class="badge badge-phoenix badge-phoenix-success">Active</span>
                                @else<span class="badge badge-phoenix badge-phoenix-secondary">Inactive</span>@endif
                            </td>
                            <td class="text-end">
                                <div class="table-actions justify-content-end">
                                    <a href="{{ route('admin.stock-locations.show', $location) }}" class="btn btn-sm btn-phoenix-primary">View stock</a>
                                    <button type="button" class="btn btn-sm btn-phoenix-secondary js-edit-location"
                                        data-location="{{ json_encode(['id' => $location->id, 'name' => $location->name, 'type' => $location->type, 'code' => $location->code, 'is_active' => $location->is_active ? 1 : 0, 'is_default' => $location->is_default ? 1 : 0]) }}"><span class="fas fa-pen"></span></button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="locationOffcanvas" style="width:min(460px,100vw);">
        <div class="offcanvas-header border-bottom border-translucent">
            <h5 class="offcanvas-title mb-0" id="locationOffcanvasLabel">Add location</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form method="POST" id="locationForm" action="{{ route('admin.stock-locations.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" id="loc-name" required placeholder="e.g. Home Storage"></div>
                <div class="row g-2 mb-3">
                    <div class="col-7"><label class="form-label">Type <span class="text-body-tertiary fs-10">(optional)</span></label>
                        <select class="form-select" name="type" id="loc-type">
                            <option value="">—</option>
                            <option value="shop">Shop</option>
                            <option value="storage">Storage</option>
                            <option value="warehouse">Warehouse</option>
                            <option value="branch">Branch</option>
                        </select>
                    </div>
                    <div class="col-5"><label class="form-label">Code</label><input class="form-control" name="code" id="loc-code" placeholder="auto"></div>
                </div>
                <div class="form-check form-switch mb-4">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="loc-active" checked>
                    <label class="form-check-label fw-semibold" for="loc-active">Active</label>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="locationSubmit">Add location</button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
        (function () {
            var form = document.getElementById('locationForm');
            var label = document.getElementById('locationOffcanvasLabel');
            var submit = document.getElementById('locationSubmit');

            document.getElementById('btnNewLocation').addEventListener('click', function () {
                form.action = '{{ route('admin.stock-locations.store') }}';
                var m = form.querySelector('[name="_method"]'); if (m) { m.remove(); }
                form.reset(); document.getElementById('loc-active').checked = true;
                label.textContent = 'Add location'; submit.textContent = 'Add location';
                new window.bootstrap.Offcanvas(document.getElementById('locationOffcanvas')).show();
            });

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-edit-location'); if (!btn) { return; }
                var d = JSON.parse(btn.getAttribute('data-location'));
                form.action = '/admin/stock-locations/' + d.id;
                var existing = form.querySelector('[name="_method"]');
                if (!existing) { var i = document.createElement('input'); i.type = 'hidden'; i.name = '_method'; i.value = 'PUT'; form.prepend(i); } else { existing.value = 'PUT'; }
                document.getElementById('loc-name').value = d.name || '';
                document.getElementById('loc-type').value = d.type || '';
                document.getElementById('loc-code').value = d.code || '';
                document.getElementById('loc-active').checked = !!d.is_active;
                label.textContent = 'Edit location'; submit.textContent = 'Save changes';
                new window.bootstrap.Offcanvas(document.getElementById('locationOffcanvas')).show();
            });
        })();
        </script>
    @endpush
@endsection
