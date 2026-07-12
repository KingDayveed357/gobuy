@extends('admin.layouts.app')

@section('title', 'Suppliers — gobuy admin')
@section('page-title', 'Suppliers')

@section('content')
    <x-admin.page-header title="Suppliers" subtitle="The wholesalers, importers and distributors you buy stock from. Raise a purchase order against any of them.">
        <x-slot:actions>
            <a href="{{ route('admin.purchase-orders.index') }}" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-file-invoice me-1"></span>Purchase orders</a>
            <button type="button" class="btn btn-primary btn-sm" id="btnNewSupplier"><span class="fas fa-plus me-1"></span>Add supplier</button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <x-admin.card flush>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead><tr><th>Supplier</th><th>Contact</th><th class="text-end">Orders</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td>
                                <span class="fw-semibold text-body-emphasis">{{ $supplier->name }}</span>
                                @if ($supplier->address)<br><span class="fs-10 text-body-tertiary">{{ $supplier->address }}</span>@endif
                            </td>
                            <td class="fs-9">
                                {{ $supplier->contact_name ?: '—' }}
                                @if ($supplier->phone)<br><span class="fs-10 text-body-tertiary">{{ $supplier->phone }}</span>@endif
                            </td>
                            <td class="text-end fs-9">{{ (int) $supplier->purchase_orders_count }}</td>
                            <td>
                                @if ($supplier->is_active)<span class="badge badge-phoenix badge-phoenix-success">Active</span>
                                @else<span class="badge badge-phoenix badge-phoenix-secondary">Inactive</span>@endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-phoenix-secondary js-edit-supplier"
                                    data-supplier="{{ json_encode(['id' => $supplier->id, 'name' => $supplier->name, 'contact_name' => $supplier->contact_name, 'phone' => $supplier->phone, 'email' => $supplier->email, 'address' => $supplier->address, 'notes' => $supplier->notes, 'is_active' => $supplier->is_active ? 1 : 0]) }}"><span class="fas fa-pen"></span></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty-state icon="fa-truck-field" text="No suppliers yet — add the businesses you buy stock from." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="supplierOffcanvas" style="width:min(480px,100vw);">
        <div class="offcanvas-header border-bottom border-translucent">
            <h5 class="offcanvas-title mb-0" id="supplierOffcanvasLabel">Add supplier</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form method="POST" id="supplierForm" action="{{ route('admin.suppliers.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" id="sup-name" required placeholder="e.g. Lagos Beverages Ltd"></div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="form-label">Contact person</label><input class="form-control" name="contact_name" id="sup-contact"></div>
                    <div class="col-6"><label class="form-label">Phone</label><input class="form-control" name="phone" id="sup-phone"></div>
                </div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" id="sup-email"></div>
                <div class="mb-3"><label class="form-label">Address</label><input class="form-control" name="address" id="sup-address"></div>
                <div class="mb-3"><label class="form-label">Notes</label><textarea class="form-control" name="notes" id="sup-notes" rows="2"></textarea></div>
                <div class="form-check form-switch mb-4">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="sup-active" checked>
                    <label class="form-check-label fw-semibold" for="sup-active">Active</label>
                </div>
                <button type="submit" class="btn btn-primary w-100" id="supplierSubmit">Add supplier</button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
        (function () {
            var form = document.getElementById('supplierForm');
            var label = document.getElementById('supplierOffcanvasLabel');
            var submit = document.getElementById('supplierSubmit');
            var set = function (id, v) { document.getElementById(id).value = v || ''; };

            document.getElementById('btnNewSupplier').addEventListener('click', function () {
                form.action = '{{ route('admin.suppliers.store') }}';
                var m = form.querySelector('[name="_method"]'); if (m) { m.remove(); }
                form.reset(); document.getElementById('sup-active').checked = true;
                label.textContent = 'Add supplier'; submit.textContent = 'Add supplier';
                new window.bootstrap.Offcanvas(document.getElementById('supplierOffcanvas')).show();
            });

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-edit-supplier'); if (!btn) { return; }
                var d = JSON.parse(btn.getAttribute('data-supplier'));
                form.action = '/admin/suppliers/' + d.id;
                var existing = form.querySelector('[name="_method"]');
                if (!existing) { var i = document.createElement('input'); i.type = 'hidden'; i.name = '_method'; i.value = 'PUT'; form.prepend(i); } else { existing.value = 'PUT'; }
                set('sup-name', d.name); set('sup-contact', d.contact_name); set('sup-phone', d.phone);
                set('sup-email', d.email); set('sup-address', d.address); set('sup-notes', d.notes);
                document.getElementById('sup-active').checked = !!d.is_active;
                label.textContent = 'Edit supplier'; submit.textContent = 'Save changes';
                new window.bootstrap.Offcanvas(document.getElementById('supplierOffcanvas')).show();
            });
        })();
        </script>
    @endpush
@endsection
