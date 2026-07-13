@extends('admin.layouts.app')

@section('title', 'Collections — Quintessential Mart admin')
@section('page-title', 'Collections')

@section('content')
    <x-admin.page-header title="Curated collections" subtitle="Hand-picked product lists you can feature via a “Curated collection” homepage section.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary btn-sm" id="btnNewCollection"><span class="fas fa-plus me-1"></span>New collection</button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <x-admin.card title="All collections" flush>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead><tr><th>Name</th><th class="text-center">Products</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse ($collections as $collection)
                        <tr>
                            <td>
                                <a href="{{ route('admin.collections.show', $collection) }}" class="fw-semibold text-body-emphasis text-decoration-none">{{ $collection->name }}</a>
                                @if ($collection->description)<p class="fs-10 text-body-tertiary mb-0 text-truncate" style="max-width:280px;">{{ $collection->description }}</p>@endif
                            </td>
                            <td class="text-center">{{ $collection->products_count }}</td>
                            <td><span class="badge badge-phoenix {{ $collection->is_active ? 'badge-phoenix-success' : 'badge-phoenix-secondary' }}">{{ $collection->is_active ? 'Active' : 'Hidden' }}</span></td>
                            <td class="text-end">
                                <div class="table-actions justify-content-end">
                                    <a href="{{ route('admin.collections.show', $collection) }}" class="btn btn-sm btn-phoenix-primary">Manage products</a>
                                    <button type="button" class="btn btn-sm btn-phoenix-secondary js-edit-collection"
                                        data-id="{{ $collection->id }}"
                                        data-collection="{{ json_encode(['name' => $collection->name, 'description' => $collection->description, 'is_active' => $collection->is_active ? 1 : 0]) }}"><span class="fas fa-pen"></span></button>
                                    <button type="button" class="btn btn-sm btn-phoenix-danger" data-bs-toggle="modal" data-bs-target="#actionModal"
                                        data-action="{{ route('admin.collections.destroy', $collection) }}" data-method="DELETE"
                                        data-title="Delete collection" data-message="Delete “{{ $collection->name }}”? Sections using it will fall back to empty." data-confirm-text="Yes, delete" data-variant="danger"><span class="fas fa-trash"></span></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-admin.empty-state icon="fa-layer-group" text="No collections yet. Create one, then add products." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="collectionOffcanvas" style="width:min(480px,100vw);">
        <div class="offcanvas-header border-bottom border-translucent">
            <h5 class="offcanvas-title mb-0" id="collectionLabel">New collection</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('admin.collections.store') }}" method="POST" id="collectionForm">
                @csrf
                <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" id="c-name" required placeholder="e.g. Editor's Picks"></div>
                <div class="mb-3"><label class="form-label">Description</label><input class="form-control" name="description" id="c-desc" placeholder="Optional"></div>
                <div class="form-check form-switch mb-4">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="c-active" checked>
                    <label class="form-check-label fw-semibold" for="c-active">Active</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1" id="cSubmit">Create collection</button>
                    <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
        (function () {
            var form = document.getElementById('collectionForm');
            var label = document.getElementById('collectionLabel');
            var submit = document.getElementById('cSubmit');

            document.getElementById('btnNewCollection').addEventListener('click', function () {
                form.action = '{{ route('admin.collections.store') }}';
                var m = form.querySelector('[name="_method"]'); if (m) { m.remove(); }
                form.reset(); document.getElementById('c-active').checked = true;
                label.textContent = 'New collection'; submit.textContent = 'Create collection';
                new window.bootstrap.Offcanvas(document.getElementById('collectionOffcanvas')).show();
            });

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-edit-collection'); if (!btn) { return; }
                var d = JSON.parse(btn.getAttribute('data-collection'));
                form.action = '/admin/collections/' + btn.getAttribute('data-id');
                var ex = form.querySelector('[name="_method"]');
                if (!ex) { var i = document.createElement('input'); i.type = 'hidden'; i.name = '_method'; i.value = 'PUT'; form.prepend(i); } else { ex.value = 'PUT'; }
                document.getElementById('c-name').value = d.name || '';
                document.getElementById('c-desc').value = d.description || '';
                document.getElementById('c-active').checked = !!d.is_active;
                label.textContent = 'Edit collection'; submit.textContent = 'Save changes';
                new window.bootstrap.Offcanvas(document.getElementById('collectionOffcanvas')).show();
            });
        })();
        </script>
    @endpush
@endsection
