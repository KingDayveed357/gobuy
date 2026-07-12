@extends('admin.layouts.app')

@section('title', 'Bulk image import — gobuy admin')

@section('content')
    <x-admin.page-header title="Bulk product images" subtitle="Upload a ZIP of images named by SKU to attach them to products in bulk">
        <x-slot:actions>
            <a href="{{ route('admin.inventory.import.create') }}" class="btn btn-phoenix-secondary">Import products (CSV)</a>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-phoenix-secondary">Back to inventory</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="row g-4 align-items-start">
        <div class="col-12 col-xl-8">
            @if (! $report)
                <x-admin.card title="Upload image archive (ZIP)">
                    <form action="{{ route('admin.inventory.import.images.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <label for="zipFile" class="admin-dropzone d-flex flex-column flex-center text-center p-4 p-lg-5 mb-3" style="cursor:pointer;">
                            <div class="admin-media-icon mb-3"><span class="fas fa-file-zipper"></span></div>
                            <h6 class="mb-1">Choose a ZIP file</h6>
                            <p class="fs-9 text-body-tertiary mb-0">Up to 100MB. You'll preview the matches before anything is saved.</p>
                            <input class="form-control mt-3" style="max-width: 360px;" type="file" id="zipFile" name="file" accept=".zip,application/zip" required>
                        </label>
                        @error('file')<p class="text-danger fs-9">{{ $message }}</p>@enderror
                        <button type="submit" class="btn btn-primary"><span class="fas fa-eye me-2"></span>Preview matches</button>
                    </form>
                </x-admin.card>
            @else
                <x-admin.card title="Preview" subtitle="Review the matches below, then confirm to attach the images.">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge badge-phoenix badge-phoenix-success">{{ $summary['match'] }} to attach</span>
                        <span class="badge badge-phoenix badge-phoenix-danger">{{ $summary['skip'] }} skipped</span>
                    </div>

                    <div class="table-responsive" style="max-height: 460px; overflow-y: auto;">
                        <table class="table table-sm admin-table mb-0 align-middle fs-9">
                            <thead>
                                <tr><th>File</th><th>SKU</th><th>Product</th><th>Status</th><th>Notes</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($report as $row)
                                    <tr class="{{ $row['status'] === 'skip' ? 'table-danger' : '' }}">
                                        <td class="fw-semibold line-clamp-1">{{ $row['file'] }}</td>
                                        <td>{{ $row['sku'] ?: '—' }}</td>
                                        <td class="line-clamp-1">{{ $row['product'] ?: '—' }}</td>
                                        <td>
                                            @if ($row['status'] === 'match')
                                                <span class="badge badge-phoenix badge-phoenix-success">Attach</span>
                                            @else
                                                <span class="badge badge-phoenix badge-phoenix-danger">Skip</span>
                                            @endif
                                        </td>
                                        <td class="text-body-tertiary">{{ $row['reason'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('admin.inventory.import.images') }}" class="btn btn-phoenix-secondary">Upload a different archive</a>
                        <form action="{{ route('admin.inventory.import.images.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <button type="submit" class="btn btn-primary" @disabled($summary['match'] === 0)>
                                <span class="fas fa-check me-2"></span>Attach {{ $summary['match'] }} image(s)
                            </button>
                        </form>
                    </div>
                </x-admin.card>
            @endif
        </div>

        <div class="col-12 col-xl-4">
            <x-admin.card title="How matching works">
                <p class="fs-9 text-body-tertiary mb-2">Name each image after the product's <strong>SKU</strong>:</p>
                <ul class="fs-9 mb-3">
                    <li><code>BEER-STAR-60.jpg</code> → the product with that SKU</li>
                    <li><code>BEER-STAR-60-1.jpg</code>, <code>BEER-STAR-60_2.png</code> → extra images for the same product</li>
                </ul>
                <p class="fs-10 text-body-tertiary mb-0">
                    Supported: JPG, PNG, WEBP, GIF (max 8MB each). Files that aren't images, or whose SKU has no product,
                    are listed as skipped. Images are added to the product gallery — existing images are kept.
                </p>
            </x-admin.card>
        </div>
    </div>
@endsection
