@php($product = $product ?? null)
@php($variant = $product?->primaryVariant())
@php($mediaCollection = \App\Modules\Catalog\Models\Product::MEDIA_COLLECTION)
@php($existingMedia = $product ? $product->getMedia($mediaCollection) : collect())
@php($extraVariants = $product ? $product->variants->where('is_default', false)->values() : collect())
@php($tiers = $product ? $product->quantityDiscounts : collect())

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
        <x-admin.card title="Basic information" class="mb-4">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Product title</label>
                    <input class="form-control form-control-lg" type="text" name="name" value="{{ old('name', $product->name ?? '') }}" placeholder="Write a clear product title" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="6" placeholder="Describe features, materials, use cases…">{{ old('description', $product->description ?? '') }}</textarea>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="Display images" class="mb-4">
            <div id="productDropzone" class="admin-dropzone d-flex flex-column flex-center text-center p-4 p-lg-5">
                <div class="admin-media-icon mb-3"><span class="fas fa-cloud-arrow-up"></span></div>
                <h6 class="mb-1">Drag your photos here <span class="text-body-tertiary fw-normal">or</span></h6>
                <label for="productImages" class="btn btn-phoenix-primary btn-sm mt-2 mb-1" style="cursor:pointer;">Browse from device</label>
                <p class="fs-9 text-body-tertiary mb-0">PNG or JPG, up to 5MB each — first image is the cover. Add up to 8.</p>
                <input class="d-none" type="file" id="productImages" name="images[]" accept="image/*" multiple>
            </div>

            {{-- New, not-yet-saved selections (rendered by JS) --}}
            <div id="imagePreviewGrid" class="admin-gallery-grid mt-3 d-none"></div>

            @if ($existingMedia->isNotEmpty())
                <p class="fs-9 fw-semibold text-body-tertiary mt-3 mb-2">Current images</p>
                <div class="admin-gallery-grid">
                    @foreach ($existingMedia as $image)
                        <div class="admin-gallery-item" data-media-id="{{ $image->id }}">
                            <img src="{{ $image->getUrl() }}" alt="">
                            <label class="admin-gallery-remove" title="Remove image">
                                <input type="checkbox" name="remove_media[]" value="{{ $image->id }}" class="d-none">
                                <span class="fas fa-trash"></span>
                            </label>
                            @if ($loop->first)<span class="admin-gallery-badge">Cover</span>@endif
                        </div>
                    @endforeach
                </div>
                <p class="fs-10 text-body-tertiary mt-2 mb-0">Click the trash icon to mark an image for removal when you save.</p>
            @endif
        </x-admin.card>

        <x-admin.card title="Default pricing &amp; stock" flush class="mb-4">
            <div class="row g-0">
                <div class="col-12 col-lg-4 border-end-lg border-bottom border-bottom-lg-0">
                    <div class="nav flex-lg-column vertical-tab h-100 fs-9 p-3 p-lg-0" role="tablist" aria-orientation="vertical">
                        <a class="nav-link d-flex align-items-center gap-2 active" data-bs-toggle="tab" data-bs-target="#pricingPane" role="tab"><span class="fas fa-tag"></span>Pricing</a>
                        <a class="nav-link d-flex align-items-center gap-2" data-bs-toggle="tab" data-bs-target="#stockPane" role="tab"><span class="fas fa-box"></span>Stock</a>
                        <a class="nav-link d-flex align-items-center gap-2" data-bs-toggle="tab" data-bs-target="#taxPane" role="tab"><span class="fas fa-percent"></span>Tax</a>
                    </div>
                </div>
                <div class="col-12 col-lg-8">
                    <div class="tab-content p-3 p-lg-4">
                        <div class="tab-pane fade show active" id="pricingPane" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Retail price (₦)</label>
                                    <div class="input-group"><span class="input-group-text">₦</span>
                                        <input class="form-control" type="number" step="0.01" min="0" name="retail_price" value="{{ old('retail_price', $variant?->retail_price?->toNaira()) }}" placeholder="0.00" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Sale price (₦) <span class="text-body-tertiary">— optional</span></label>
                                    <div class="input-group"><span class="input-group-text">₦</span>
                                        <input class="form-control" type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price', $variant?->sale_price?->toNaira()) }}" placeholder="Discounted price">
                                    </div>
                                    <p class="fs-10 text-body-tertiary mt-1 mb-0">Shows a struck-through price + “% off” to retail shoppers.</p>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Wholesale price (₦)</label>
                                    <div class="input-group"><span class="input-group-text">₦</span>
                                        <input class="form-control" type="number" step="0.01" min="0" name="wholesale_price" value="{{ old('wholesale_price', $variant?->wholesale_price?->toNaira()) }}" placeholder="0.00">
                                    </div>
                                    <p class="fs-10 text-body-tertiary mt-1 mb-0">Applied automatically to approved wholesale customers.</p>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="stockPane" role="tabpanel">
                            <label class="form-label">Stock on hand</label>
                            <input class="form-control" type="number" min="0" name="stock" value="{{ old('stock', $variant?->stock ?? 0) }}" required style="max-width: 200px;">
                            <p class="fs-10 text-body-tertiary mt-1 mb-0">Units available for the default variant.</p>
                        </div>
                        <div class="tab-pane fade" id="taxPane" role="tabpanel">
                            <div class="mb-3" style="max-width: 220px;">
                                <label class="form-label">VAT rate (%)</label>
                                <input class="form-control" type="number" step="0.01" min="0" max="100" name="vat_rate" value="{{ old('vat_rate', $product->vat_rate ?? 7.5) }}">
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="is_vat_inclusive" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_vat_inclusive" value="1" id="vatInclusive" @checked(old('is_vat_inclusive', $product->is_vat_inclusive ?? true))>
                                <label class="form-check-label" for="vatInclusive">Price includes VAT</label>
                            </div>
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_tax_exempt" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_tax_exempt" value="1" id="taxExempt" @checked(old('is_tax_exempt', $product->is_tax_exempt ?? false))>
                                <label class="form-check-label" for="taxExempt">Tax exempt</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-admin.card>

        @php($options = $product ? $product->options()->with('values')->get() : collect())
        <x-admin.card title="Options" subtitle="Define option axes (e.g. Colour, Size) and their values. Variants reference these values." class="mb-4">
            <x-slot:cardActions>
                <button type="button" class="btn btn-phoenix-primary btn-sm" id="addOptionBtn"><span class="fas fa-plus me-1"></span>Add option</button>
            </x-slot:cardActions>

            <div id="optionRows" class="admin-repeater">
                @foreach ($options as $i => $option)
                    <div class="admin-repeater-row" data-option-row>
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label fs-10">Option name</label>
                                <input class="form-control form-control-sm" name="options[{{ $i }}][name]" value="{{ $option->name }}" placeholder="e.g. Colour">
                            </div>
                            <div class="col-10 col-md-7">
                                <label class="form-label fs-10">Values <span class="text-body-tertiary">— comma-separated</span></label>
                                <input class="form-control form-control-sm" name="options[{{ $i }}][values]" value="{{ $option->values->pluck('value')->implode(', ') }}" placeholder="e.g. Red, Blue, Black">
                            </div>
                            <div class="col-2 col-md-1 text-end">
                                <button type="button" class="btn btn-sm btn-phoenix-danger" data-remove-row title="Remove"><span class="fas fa-trash"></span></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p id="optionEmpty" class="fs-9 text-body-tertiary mb-0 {{ $options->isNotEmpty() ? 'd-none' : '' }}">No options yet. Add one to offer choices like colour or size.</p>
        </x-admin.card>

        <x-admin.card title="Specifications" subtitle="Key technical details shown on the product page (e.g. Material, Standard, Weight)." class="mb-4">
            <x-slot:cardActions>
                <button type="button" class="btn btn-phoenix-primary btn-sm" id="addSpecBtn"><span class="fas fa-plus me-1"></span>Add specification</button>
            </x-slot:cardActions>

            @php($specs = $product ? $product->specifications : collect())
            <div id="specRows" class="admin-repeater">
                @foreach ($specs as $i => $spec)
                    <div class="admin-repeater-row" data-spec-row>
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label fs-10">Label</label>
                                <input class="form-control form-control-sm" name="specifications[{{ $i }}][label]" value="{{ $spec->label }}" placeholder="e.g. Material">
                            </div>
                            <div class="col-10 col-md-7">
                                <label class="form-label fs-10">Value</label>
                                <input class="form-control form-control-sm" name="specifications[{{ $i }}][value]" value="{{ $spec->value }}" placeholder="e.g. High-density polyethylene">
                            </div>
                            <div class="col-2 col-md-1 text-end">
                                <button type="button" class="btn btn-sm btn-phoenix-danger" data-remove-row title="Remove"><span class="fas fa-trash"></span></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p id="specEmpty" class="fs-9 text-body-tertiary mb-0 {{ $specs->isNotEmpty() ? 'd-none' : '' }}">No specifications yet.</p>
        </x-admin.card>

        <x-admin.card title="Variants" subtitle="Add options like size or colour. Each variant has its own SKU, price and stock." class="mb-4">
            <x-slot:cardActions>
                <button type="button" class="btn btn-phoenix-primary btn-sm" id="addVariantBtn"><span class="fas fa-plus me-1"></span>Add variant</button>
            </x-slot:cardActions>

            <div id="variantRows" class="admin-repeater">
                @foreach ($extraVariants as $i => $v)
                    <div class="admin-repeater-row" data-variant-row>
                        <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v->id }}">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label fs-10">Variant name</label>
                                <input class="form-control form-control-sm" name="variants[{{ $i }}][name]" value="{{ $v->name }}" placeholder="e.g. Red / Large">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fs-10">SKU</label>
                                <input class="form-control form-control-sm" name="variants[{{ $i }}][sku]" value="{{ $v->sku }}" placeholder="SKU">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fs-10">Retail (₦)</label>
                                <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="variants[{{ $i }}][retail_price]" value="{{ $v->retail_price?->toNaira() }}">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fs-10">Wholesale (₦)</label>
                                <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="variants[{{ $i }}][wholesale_price]" value="{{ $v->wholesale_price?->toNaira() }}">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label fs-10">Stock</label>
                                <input class="form-control form-control-sm" type="number" min="0" name="variants[{{ $i }}][stock]" value="{{ $v->stock }}">
                            </div>
                            <div class="col-2 col-md-1 text-end">
                                <button type="button" class="btn btn-sm btn-phoenix-danger" data-remove-row title="Remove"><span class="fas fa-trash"></span></button>
                            </div>
                            <div class="col-12">
                                <label class="form-label fs-10">Option values <span class="text-body-tertiary">— comma-separated, matching the options above (e.g. Red, Large)</span></label>
                                <input class="form-control form-control-sm" name="variants[{{ $i }}][options]" value="{{ $v->optionValues->pluck('value')->implode(', ') }}" placeholder="e.g. Red, Large">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p id="variantEmpty" class="fs-9 text-body-tertiary mb-0 {{ $extraVariants->isNotEmpty() ? 'd-none' : '' }}">No extra variants — this is a single-variant product. Click “Add variant” to offer options.</p>
        </x-admin.card>

        <x-admin.card title="Wholesale quantity tiers" subtitle="Lower the unit price when approved wholesale buyers order in bulk." class="mb-4">
            <x-slot:cardActions>
                <button type="button" class="btn btn-phoenix-primary btn-sm" id="addTierBtn"><span class="fas fa-plus me-1"></span>Add tier</button>
            </x-slot:cardActions>

            <div id="tierRows" class="admin-repeater">
                @foreach ($tiers as $i => $tier)
                    <div class="admin-repeater-row" data-tier-row>
                        <div class="row g-2 align-items-end">
                            <div class="col-5 col-md-4">
                                <label class="form-label fs-10">Minimum quantity</label>
                                <input class="form-control form-control-sm" type="number" min="2" name="quantity_discounts[{{ $i }}][min_qty]" value="{{ $tier->min_qty }}">
                            </div>
                            <div class="col-5 col-md-6">
                                <label class="form-label fs-10">Unit price at this tier (₦)</label>
                                <div class="input-group input-group-sm"><span class="input-group-text">₦</span>
                                    <input class="form-control" type="number" step="0.01" min="0" name="quantity_discounts[{{ $i }}][unit_price]" value="{{ $tier->unit_price?->toNaira() }}">
                                </div>
                            </div>
                            <div class="col-2 text-end">
                                <button type="button" class="btn btn-sm btn-phoenix-danger" data-remove-row title="Remove"><span class="fas fa-trash"></span></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p id="tierEmpty" class="fs-9 text-body-tertiary mb-0 {{ $tiers->isNotEmpty() ? 'd-none' : '' }}">No tiers yet. Example: 10+ units at ₦22,000, 50+ at ₦20,000.</p>
        </x-admin.card>
    </div>

    <div class="col-12 col-xl-4">
        <x-admin.card title="Organization">
            <div class="mb-3">
                <div class="d-flex flex-between-center gap-2 mb-1">
                    <label class="form-label mb-0">Category</label>
                    <button class="btn btn-link p-0 fs-9 fw-bold text-decoration-none" type="button" data-bs-toggle="modal" data-bs-target="#addCategoryModal">+ Add new</button>
                </div>
                <x-admin.category-select id="productCategorySelect" :options="$categoryOptions" name="category_id" :selected="old('category_id', $product->category_id ?? null)" include-none />
            </div>
            <div class="mb-3">
                <label class="form-label">Brand <span class="text-body-tertiary">— optional</span></label>
                <select class="form-select" name="brand_id">
                    <option value="">No brand</option>
                    @foreach ($brands ?? [] as $brand)
                        <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id ?? null) == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Condition</label>
                <select class="form-select" name="condition" required>
                    @foreach (['new' => 'New', 'used' => 'Used', 'refurbished' => 'Refurbished'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('condition', $product->condition ?? 'new') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Default SKU</label>
                <input class="form-control" type="text" name="sku" value="{{ old('sku', $variant?->sku) }}" placeholder="Internal SKU" required>
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
                <input class="form-check-input" type="checkbox" role="switch" name="is_featured" value="1" id="is_featured" @checked(old('is_featured', $product->is_featured ?? false))>
                <label class="form-check-label" for="is_featured">Featured product</label>
                <p class="fs-10 text-body-tertiary mb-0">Featured items are highlighted across the storefront.</p>
            </div>
        </x-admin.card>

        <x-admin.card title="Shipping &amp; cost" subtitle="Used for delivery pricing and margin tracking." class="mt-4">
            <div class="mb-3">
                <label class="form-label">Weight (grams)</label>
                <input class="form-control" type="number" min="0" name="weight_g" value="{{ old('weight_g', $product->weight_g ?? '') }}" placeholder="e.g. 420">
            </div>
            <div class="row g-2 mb-3">
                <div class="col-4">
                    <label class="form-label fs-10">Length (mm)</label>
                    <input class="form-control form-control-sm" type="number" min="0" name="length_mm" value="{{ old('length_mm', $product->length_mm ?? '') }}">
                </div>
                <div class="col-4">
                    <label class="form-label fs-10">Width (mm)</label>
                    <input class="form-control form-control-sm" type="number" min="0" name="width_mm" value="{{ old('width_mm', $product->width_mm ?? '') }}">
                </div>
                <div class="col-4">
                    <label class="form-label fs-10">Height (mm)</label>
                    <input class="form-control form-control-sm" type="number" min="0" name="height_mm" value="{{ old('height_mm', $product->height_mm ?? '') }}">
                </div>
            </div>
            <div class="mb-0">
                <label class="form-label">Landed cost (USD) <span class="text-body-tertiary">— internal</span></label>
                <div class="input-group"><span class="input-group-text">$</span>
                    <input class="form-control" type="number" step="0.01" min="0" name="cost_price_usd" value="{{ old('cost_price_usd', isset($product->cost_price_usd) ? number_format($product->cost_price_usd / 100, 2, '.', '') : '') }}" placeholder="0.00">
                </div>
                <p class="fs-10 text-body-tertiary mt-1 mb-0">Stored privately for margin tracking against the Naira price.</p>
            </div>
        </x-admin.card>
    </div>
</div>

{{-- Row templates for the JS repeaters --}}
<template id="variantRowTemplate">
    <div class="admin-repeater-row" data-variant-row>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label fs-10">Variant name</label>
                <input class="form-control form-control-sm" name="variants[__I__][name]" placeholder="e.g. Red / Large">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fs-10">SKU</label>
                <input class="form-control form-control-sm" name="variants[__I__][sku]" placeholder="SKU">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fs-10">Retail (₦)</label>
                <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="variants[__I__][retail_price]">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fs-10">Wholesale (₦)</label>
                <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="variants[__I__][wholesale_price]">
            </div>
            <div class="col-4 col-md-2">
                <label class="form-label fs-10">Stock</label>
                <input class="form-control form-control-sm" type="number" min="0" name="variants[__I__][stock]" value="0">
            </div>
            <div class="col-2 col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-phoenix-danger" data-remove-row title="Remove"><span class="fas fa-trash"></span></button>
            </div>
        </div>
    </div>
</template>

<template id="tierRowTemplate">
    <div class="admin-repeater-row" data-tier-row>
        <div class="row g-2 align-items-end">
            <div class="col-5 col-md-4">
                <label class="form-label fs-10">Minimum quantity</label>
                <input class="form-control form-control-sm" type="number" min="2" name="quantity_discounts[__I__][min_qty]">
            </div>
            <div class="col-5 col-md-6">
                <label class="form-label fs-10">Unit price at this tier (₦)</label>
                <div class="input-group input-group-sm"><span class="input-group-text">₦</span>
                    <input class="form-control" type="number" step="0.01" min="0" name="quantity_discounts[__I__][unit_price]">
                </div>
            </div>
            <div class="col-2 text-end">
                <button type="button" class="btn btn-sm btn-phoenix-danger" data-remove-row title="Remove"><span class="fas fa-trash"></span></button>
            </div>
        </div>
    </div>
</template>

<template id="optionRowTemplate">
    <div class="admin-repeater-row" data-option-row>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label fs-10">Option name</label>
                <input class="form-control form-control-sm" name="options[__I__][name]" placeholder="e.g. Colour">
            </div>
            <div class="col-10 col-md-7">
                <label class="form-label fs-10">Values <span class="text-body-tertiary">— comma-separated</span></label>
                <input class="form-control form-control-sm" name="options[__I__][values]" placeholder="e.g. Red, Blue, Black">
            </div>
            <div class="col-2 col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-phoenix-danger" data-remove-row title="Remove"><span class="fas fa-trash"></span></button>
            </div>
        </div>
    </div>
</template>

<template id="specRowTemplate">
    <div class="admin-repeater-row" data-spec-row>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label fs-10">Label</label>
                <input class="form-control form-control-sm" name="specifications[__I__][label]" placeholder="e.g. Material">
            </div>
            <div class="col-10 col-md-7">
                <label class="form-label fs-10">Value</label>
                <input class="form-control form-control-sm" name="specifications[__I__][value]" placeholder="e.g. Steel">
            </div>
            <div class="col-2 col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-phoenix-danger" data-remove-row title="Remove"><span class="fas fa-trash"></span></button>
            </div>
        </div>
    </div>
</template>

<script>
    (function () {
        // ---- Image dropzone preview ----
        var input = document.getElementById('productImages');
        var dz = document.getElementById('productDropzone');
        var grid = document.getElementById('imagePreviewGrid');

        function renderPreviews() {
            grid.innerHTML = '';
            var files = Array.prototype.slice.call(input.files || []);
            if (!files.length) { grid.classList.add('d-none'); return; }
            grid.classList.remove('d-none');
            files.forEach(function (file) {
                if (!file.type.startsWith('image/')) { return; }
                var item = document.createElement('div');
                item.className = 'admin-gallery-item';
                var img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onload = function () { URL.revokeObjectURL(img.src); };
                item.appendChild(img);
                var badge = document.createElement('span');
                badge.className = 'admin-gallery-badge admin-gallery-badge--new';
                badge.textContent = 'New';
                item.appendChild(badge);
                grid.appendChild(item);
            });
        }

        if (input) {
            input.addEventListener('change', renderPreviews);
            ['dragover', 'dragenter'].forEach(function (e) {
                dz.addEventListener(e, function (ev) { ev.preventDefault(); dz.classList.add('is-dragging'); });
            });
            ['dragleave', 'drop'].forEach(function (e) {
                dz.addEventListener(e, function (ev) { ev.preventDefault(); dz.classList.remove('is-dragging'); });
            });
            dz.addEventListener('drop', function (ev) {
                if (ev.dataTransfer && ev.dataTransfer.files.length) {
                    input.files = ev.dataTransfer.files;
                    renderPreviews();
                }
            });
        }

        // ---- Existing image removal toggle ----
        document.querySelectorAll('.admin-gallery-remove').forEach(function (label) {
            var cb = label.querySelector('input[type="checkbox"]');
            label.addEventListener('click', function () {
                // let the checkbox toggle, then reflect state
                setTimeout(function () {
                    label.closest('.admin-gallery-item').classList.toggle('is-removed', cb.checked);
                }, 0);
            });
        });

        // ---- Generic repeater ----
        function setupRepeater(opts) {
            var container = document.getElementById(opts.container);
            var addBtn = document.getElementById(opts.addBtn);
            var template = document.getElementById(opts.template);
            var empty = document.getElementById(opts.empty);
            var counter = container.querySelectorAll('[' + opts.rowAttr + ']').length;

            function refreshEmpty() {
                if (!empty) { return; }
                empty.classList.toggle('d-none', container.querySelector('[' + opts.rowAttr + ']') !== null);
            }

            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    var html = template.innerHTML.replace(/__I__/g, 'new_' + (counter++));
                    var wrap = document.createElement('div');
                    wrap.innerHTML = html.trim();
                    container.appendChild(wrap.firstChild);
                    refreshEmpty();
                });
            }

            container.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-remove-row]');
                if (!btn) { return; }
                btn.closest('[' + opts.rowAttr + ']').remove();
                refreshEmpty();
            });
        }

        setupRepeater({ container: 'optionRows', addBtn: 'addOptionBtn', template: 'optionRowTemplate', empty: 'optionEmpty', rowAttr: 'data-option-row' });
        setupRepeater({ container: 'specRows', addBtn: 'addSpecBtn', template: 'specRowTemplate', empty: 'specEmpty', rowAttr: 'data-spec-row' });
        setupRepeater({ container: 'variantRows', addBtn: 'addVariantBtn', template: 'variantRowTemplate', empty: 'variantEmpty', rowAttr: 'data-variant-row' });
        setupRepeater({ container: 'tierRows', addBtn: 'addTierBtn', template: 'tierRowTemplate', empty: 'tierEmpty', rowAttr: 'data-tier-row' });
    })();
</script>
