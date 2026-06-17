@php($product = $product ?? null)

@if ($errors->any())
    <div class="alert alert-subtle-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4 align-items-start">
    <div class="col-12 col-xl-8">
        <x-admin.card title="Basic information" subtitle="Give shoppers the context they need to buy confidently." class="mb-4">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Product title</label>
                    <input class="form-control form-control-lg" type="text" name="name" value="{{ old('name', $product->name ?? '') }}" placeholder="Write a clear product title" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="6" placeholder="Describe features, materials, use cases, and any important details">{{ old('description', $product->description ?? '') }}</textarea>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Media" subtitle="A polished upload flow can slot in here later without restructuring the page." class="mb-4">
            <div class="admin-media-placeholder d-flex flex-column flex-center text-center p-4 p-lg-5">
                <div class="admin-media-icon mb-3">
                    <span class="fas fa-cloud-arrow-up"></span>
                </div>
                <h6 class="mb-1">Image uploads coming soon</h6>
                <p class="fs-9 text-body-tertiary mb-0">The page stays ready for a future drag-and-drop uploader and still shows existing images below.</p>
                @if ($product && $product->images->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mt-4 justify-content-center">
                        @foreach ($product->images as $image)
                            <img class="admin-media-thumb" src="{{ asset($image->path) }}" width="72" height="72" alt="">
                        @endforeach
                    </div>
                @endif
            </div>
        </x-admin.card>

        <x-admin.card title="Inventory & pricing" subtitle="Separate pricing and stock details into a clean responsive grid." flush>
            <div class="row g-0">
                <div class="col-12 col-lg-4 border-end-lg border-bottom border-bottom-lg-0">
                    <div class="nav flex-lg-column vertical-tab h-100 fs-9 p-3 p-lg-0" role="tablist" aria-orientation="vertical">
                        <a class="nav-link d-flex align-items-center gap-2 active" id="pricingTab" data-bs-toggle="tab" data-bs-target="#pricingPane" role="tab" aria-selected="true">
                            <span data-feather="tag" style="height:16px;width:16px;"></span>Pricing
                        </a>
                        <a class="nav-link d-flex align-items-center gap-2" id="stockTab" data-bs-toggle="tab" data-bs-target="#stockPane" role="tab" aria-selected="false">
                            <span data-feather="package" style="height:16px;width:16px;"></span>Stock
                        </a>
                    </div>
                </div>
                <div class="col-12 col-lg-8">
                    <div class="tab-content p-3 p-lg-4">
                        <div class="tab-pane fade show active" id="pricingPane" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Retail price (₦)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₦</span>
                                        <input class="form-control" type="number" step="0.01" min="0" name="retail_price" value="{{ old('retail_price', $product->retail_price ?? '') }}" placeholder="0.00" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Wholesale price (₦)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₦</span>
                                        <input class="form-control" type="number" step="0.01" min="0" name="wholesale_price" value="{{ old('wholesale_price', $product->wholesale_price ?? '') }}" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="admin-inline-note">
                                        <div>
                                            <label class="form-label mb-1">Wholesale minimum quantity</label>
                                            <p class="fs-10 text-body-tertiary mb-0">Wholesale pricing activates from this quantity upward.</p>
                                        </div>
                                        <input class="form-control" type="number" min="1" name="wholesale_min_qty" value="{{ old('wholesale_min_qty', $product->wholesale_min_qty ?? 1) }}" required style="max-width: 180px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="stockPane" role="tabpanel">
                            <div class="admin-inline-note">
                                <div>
                                    <label class="form-label mb-1">Stock on hand</label>
                                    <p class="fs-10 text-body-tertiary mb-0">Units available for sale right now.</p>
                                </div>
                                <input class="form-control" type="number" min="0" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required style="max-width: 180px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-admin.card>
    </div>

    <div class="col-12 col-xl-4">
        <x-admin.card title="Organization" subtitle="Keep the catalog structured and publish-ready.">
            <div class="mb-3">
                <div class="d-flex flex-between-center gap-2 mb-1">
                    <label class="form-label mb-0">Category</label>
                    <button class="btn btn-link p-0 fs-9 fw-bold text-decoration-none" type="button" data-bs-toggle="modal" data-bs-target="#addCategoryModal">+ Add new</button>
                </div>
                <x-admin.category-select id="productCategorySelect" :options="$categoryOptions" name="category_id" :selected="old('category_id', $product->category_id ?? null)" required />
            </div>
            <div class="mb-3">
                <label class="form-label">SKU</label>
                <input class="form-control" type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" placeholder="Internal SKU" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" required>
                    @foreach (['draft', 'active', 'archived'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $product->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-check form-switch admin-switch-card mb-0">
                <input type="hidden" name="is_featured" value="0">
                <input class="form-check-input" type="checkbox" role="switch" name="is_featured" value="1" id="is_featured"
                       @checked(old('is_featured', $product->is_featured ?? false))>
                <label class="form-check-label" for="is_featured">Featured product</label>
                <p class="fs-10 text-body-tertiary mb-0">Featured items can be highlighted across the storefront.</p>
            </div>
        </x-admin.card>
    </div>
</div>
