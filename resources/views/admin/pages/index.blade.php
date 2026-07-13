@extends('admin.layouts.app')

@section('title', 'Pages — Quintessential Mart admin')
@section('page-title', 'Pages')

@section('content')
    <x-admin.page-header title="Storefront pages" subtitle="The homepage and campaign landing pages — each built from content blocks.">
        <x-slot:actions>
            <button type="button" class="btn btn-primary btn-sm" id="btnNewPage"><span class="fas fa-plus me-1"></span>New page</button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <x-admin.card title="All pages" flush>
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead><tr><th>Page</th><th>URL</th><th class="text-center">Sections</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @foreach ($pages as $page)
                        <tr>
                            <td>
                                <span class="fw-semibold text-body-emphasis">{{ $page->title }}</span>
                                @if ($page->isHome())<span class="badge badge-phoenix badge-phoenix-info ms-1">Home</span>@endif
                            </td>
                            <td class="fs-10 text-body-tertiary">{{ $page->isHome() ? '/' : '/p/'.$page->slug }}</td>
                            <td class="text-center">{{ $page->sections_count }}</td>
                            <td><span class="badge badge-phoenix {{ $page->isPublished() ? 'badge-phoenix-success' : 'badge-phoenix-warning' }}">{{ ucfirst($page->status) }}</span></td>
                            <td class="text-end">
                                <div class="table-actions justify-content-end">
                                    <a href="{{ route('admin.merchandising.index', ['page' => $page->slug]) }}" class="btn btn-sm btn-phoenix-primary">Edit content</a>
                                    <a href="{{ $page->url() }}" target="_blank" class="btn btn-sm btn-phoenix-secondary" title="View live"><span class="fas fa-external-link"></span></a>
                                    @unless ($page->isHome())
                                        <button type="button" class="btn btn-sm btn-phoenix-secondary js-edit-page"
                                            data-id="{{ $page->id }}"
                                            data-page="{{ json_encode(['title' => $page->title, 'slug' => $page->slug, 'meta_title' => $page->meta_title, 'meta_description' => $page->meta_description, 'status' => $page->status]) }}"><span class="fas fa-pen"></span></button>
                                        <button type="button" class="btn btn-sm btn-phoenix-danger" data-bs-toggle="modal" data-bs-target="#actionModal"
                                            data-action="{{ route('admin.pages.destroy', $page) }}" data-method="DELETE"
                                            data-title="Delete page" data-message="Delete “{{ $page->title }}” and all its sections?" data-confirm-text="Yes, delete" data-variant="danger"><span class="fas fa-trash"></span></button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-phoenix-secondary js-edit-page"
                                            data-id="{{ $page->id }}"
                                            data-page="{{ json_encode(['title' => $page->title, 'slug' => $page->slug, 'meta_title' => $page->meta_title, 'meta_description' => $page->meta_description, 'status' => $page->status, 'home' => true]) }}"><span class="fas fa-pen"></span></button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="pageOffcanvas" style="width:min(520px,100vw);">
        <div class="offcanvas-header border-bottom border-translucent">
            <h5 class="offcanvas-title mb-0" id="pageLabel">New page</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('admin.pages.store') }}" method="POST" id="pageForm">
                @csrf
                <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" id="p-title" required placeholder="e.g. Black Friday"></div>
                <div class="mb-3">
                    <label class="form-label">URL slug <span class="text-body-tertiary fs-10">(leave blank to auto-generate)</span></label>
                    <div class="input-group"><span class="input-group-text">/p/</span><input class="form-control" name="slug" id="p-slug" placeholder="black-friday"></div>
                </div>
                <div class="mb-3"><label class="form-label">SEO title</label><input class="form-control" name="meta_title" id="p-meta-title" placeholder="Optional — defaults to the title"></div>
                <div class="mb-3"><label class="form-label">SEO description</label><textarea class="form-control" name="meta_description" id="p-meta-desc" rows="2" placeholder="Optional"></textarea></div>
                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" id="p-status">
                        <option value="draft">Draft — reachable only via Preview</option>
                        <option value="published">Published — live at its URL</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1" id="pSubmit">Create page</button>
                    <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
        (function () {
            var form = document.getElementById('pageForm');
            var label = document.getElementById('pageLabel');
            var submit = document.getElementById('pSubmit');
            var slugInput = document.getElementById('p-slug');

            document.getElementById('btnNewPage').addEventListener('click', function () {
                form.action = '{{ route('admin.pages.store') }}';
                var m = form.querySelector('[name="_method"]'); if (m) { m.remove(); }
                form.reset();
                slugInput.disabled = false;
                document.getElementById('p-status').value = 'draft';
                label.textContent = 'New page'; submit.textContent = 'Create page';
                new window.bootstrap.Offcanvas(document.getElementById('pageOffcanvas')).show();
            });

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-edit-page'); if (!btn) { return; }
                var d = JSON.parse(btn.getAttribute('data-page'));
                form.action = '/admin/pages/' + btn.getAttribute('data-id');
                var ex = form.querySelector('[name="_method"]');
                if (!ex) { var i = document.createElement('input'); i.type = 'hidden'; i.name = '_method'; i.value = 'PUT'; form.prepend(i); } else { ex.value = 'PUT'; }
                document.getElementById('p-title').value = d.title || '';
                slugInput.value = d.slug || '';
                slugInput.disabled = !!d.home; // home slug is fixed
                document.getElementById('p-meta-title').value = d.meta_title || '';
                document.getElementById('p-meta-desc').value = d.meta_description || '';
                document.getElementById('p-status').value = d.status || 'published';
                label.textContent = 'Edit page'; submit.textContent = 'Save changes';
                new window.bootstrap.Offcanvas(document.getElementById('pageOffcanvas')).show();
            });
        })();
        </script>
    @endpush
@endsection
