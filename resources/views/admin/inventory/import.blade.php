@extends('admin.layouts.app')

@section('title', 'Bulk import — Quintessential Mart admin')

@section('content')
    <x-admin.page-header title="Bulk product import" subtitle="Upload a CSV to create and update products in bulk">
        <x-slot:actions>
            <a href="{{ route('admin.inventory.import.template') }}" class="btn btn-phoenix-primary">
                <span class="fas fa-download me-2"></span>Download template
            </a>
            <a href="{{ route('admin.inventory.import.images') }}" class="btn btn-phoenix-secondary">
                <span class="fas fa-images me-2"></span>Import images
            </a>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-phoenix-secondary">Back to inventory</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-4 align-items-start">
        <div class="col-12 col-xl-8">
            @if (! $report)
                <x-admin.card title="Upload CSV">
                    <form action="{{ route('admin.inventory.import.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <label for="importFile" class="admin-dropzone d-flex flex-column flex-center text-center p-4 p-lg-5 mb-3" style="cursor:pointer;">
                            <div class="admin-media-icon mb-3"><span class="fas fa-file-csv"></span></div>
                            <h6 class="mb-1">Choose a CSV file</h6>
                            <p class="fs-9 text-body-tertiary mb-0">Up to 5MB. You'll preview and confirm before anything is saved.</p>
                            <input class="form-control mt-3" style="max-width: 360px;" type="file" id="importFile" name="file" accept=".csv,text/csv" required>
                        </label>
                        @error('file')<p class="text-danger fs-9">{{ $message }}</p>@enderror
                        <div class="alert alert-subtle-info fs-9 d-flex align-items-center gap-2 py-2">
                            <span class="fas fa-circle-info"></span>
                            <span>New here? <a href="{{ route('admin.inventory.import.template') }}">Download the starter template</a> — it opens in Excel, pre-filled with a sample Nigerian catalogue you can edit or import as-is.</span>
                        </div>
                        <button type="submit" class="btn btn-primary"><span class="fas fa-eye me-2"></span>Preview import</button>
                    </form>
                </x-admin.card>
            @else
                <x-admin.card title="Preview" subtitle="Review the rows below, then confirm to import the valid ones.">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge badge-phoenix badge-phoenix-success">{{ $summary['create'] }} to create</span>
                        <span class="badge badge-phoenix badge-phoenix-info">{{ $summary['update'] }} to update</span>
                        <span class="badge badge-phoenix badge-phoenix-danger">{{ $summary['error'] }} with errors (skipped)</span>
                    </div>

                    <div class="table-responsive" style="max-height: 460px; overflow-y: auto;">
                        <table class="table table-sm admin-table mb-0 align-middle fs-9">
                            <thead>
                                <tr><th>Line</th><th>SKU</th><th>Name</th><th>Action</th><th>Notes</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($report as $row)
                                    <tr class="{{ $row['action'] === 'error' ? 'table-danger' : '' }}">
                                        <td>{{ $row['line'] }}</td>
                                        <td class="fw-semibold">{{ $row['sku'] ?: '—' }}</td>
                                        <td class="line-clamp-1">{{ $row['name'] ?: '—' }}</td>
                                        <td>
                                            @if ($row['action'] === 'create')
                                                <span class="badge badge-phoenix badge-phoenix-success">Create</span>
                                            @elseif ($row['action'] === 'update')
                                                <span class="badge badge-phoenix badge-phoenix-info">Update</span>
                                            @else
                                                <span class="badge badge-phoenix badge-phoenix-danger">Error</span>
                                            @endif
                                        </td>
                                        <td class="text-body-tertiary">{{ implode(' ', $row['errors']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('admin.inventory.import.create') }}" class="btn btn-phoenix-secondary">Upload a different file</a>
                        <form action="{{ route('admin.inventory.import.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <button type="submit" class="btn btn-primary" @disabled($summary['create'] + $summary['update'] === 0)>
                                <span class="fas fa-check me-2"></span>Import {{ $summary['create'] + $summary['update'] }} valid row(s)
                            </button>
                        </form>
                    </div>
                </x-admin.card>
            @endif
        </div>

        <div class="col-12 col-xl-4">
            <x-admin.card title="CSV format">
                <p class="fs-9 text-body-tertiary mb-2">First row must be a header. Recognised columns (the <a href="{{ route('admin.inventory.import.template') }}">template</a> has them all):</p>
                <ul class="fs-9 mb-3">
                    <li><code>SKU</code> <span class="text-body-tertiary">(required — matches an existing variant to update)</span></li>
                    <li><code>Product Name</code>, <code>Category</code>, <code>Brand</code>, <code>Description</code></li>
                    <li><code>Cost Price</code>, <code>Retail Price</code>, <code>Wholesale Price</code> <span class="text-body-tertiary">(Naira)</span></li>
                    <li><code>Initial Stock</code>, <code>Reorder Level</code></li>
                    <li><code>Weight</code>, <code>Length</code>, <code>Width</code>, <code>Height</code>, <code>Tax Exempt</code></li>
                    <li><code>Status</code> <span class="text-body-tertiary">(draft/active/archived)</span></li>
                </ul>
                <p class="fs-10 text-body-tertiary mb-0">Unknown SKUs create a new product; known SKUs update price &amp; stock. Categories and brands are created automatically by name. Stock changes are written to the adjustment log.</p>
            </x-admin.card>
        </div>
    </div>
@endsection
