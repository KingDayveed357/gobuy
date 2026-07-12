<div wire:key="manage-packaging">
    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <x-admin.card title="Choose a product">
                <div class="position-relative">
                    <input type="text" class="form-control" wire:model.live.debounce.250ms="search" placeholder="Search a product or SKU…" autocomplete="off">
                    @if (! empty($this->results) && count($this->results))
                        <div class="list-group position-absolute w-100 shadow-sm" style="z-index:30; max-height:280px; overflow:auto;">
                            @foreach ($this->results as $variant)
                                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" wire:click="choose({{ $variant->id }})" wire:key="pk-res-{{ $variant->id }}">
                                    <span class="min-w-0"><span class="d-block fw-semibold text-truncate fs-9">{{ $variant->is_default ? $variant->product->name : $variant->product->name.' — '.$variant->label() }}</span><span class="fs-10 text-body-tertiary">SKU {{ $variant->sku }}</span></span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($this->variant)
                    <div class="mt-3 p-3 rounded bg-body-secondary">
                        <span class="fw-semibold d-block">{{ $this->variant->is_default ? $this->variant->product->name : $this->variant->product->name.' — '.$this->variant->label() }}</span>
                        <span class="fs-10 text-body-tertiary">Base unit · SKU {{ $this->variant->sku }} · {{ $this->variant->retail_price?->format() }}</span>
                    </div>

                    <hr class="my-3">
                    <h6 class="mb-3">{{ $editingId ? 'Edit packaging' : 'Add packaging' }}</h6>
                    <div class="mb-2"><label class="form-label">Name</label><input class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="e.g. Carton">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Base units each</label><input type="number" min="1" class="form-control @error('multiplier') is-invalid @enderror" wire:model="multiplier">@error('multiplier')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-6"><label class="form-label">Price ₦ <span class="text-body-tertiary fs-10">(opt)</span></label><input type="number" min="0" step="0.01" class="form-control" wire:model="price" placeholder="auto"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Barcode <span class="text-body-tertiary fs-10">(optional)</span></label><input class="form-control" wire:model="barcode"></div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" wire:click="save"><span class="fas fa-check me-1"></span>{{ $editingId ? 'Save' : 'Add' }}</button>
                        @if ($editingId)<button class="btn btn-phoenix-secondary" wire:click="resetForm">Cancel</button>@endif
                    </div>
                @endif
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-7">
            <x-admin.card title="Packaging units" flush>
                @if (! $this->variant)
                    <div class="p-3"><x-admin.empty-state icon="fa-boxes-stacked" text="Choose a product to manage how it's packaged and sold." /></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead><tr><th>Name</th><th class="text-end">Base units</th><th class="text-end">Price each</th><th>Barcode</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold fs-9">Base unit</td>
                                    <td class="text-end fs-9">1</td>
                                    <td class="text-end fs-9">{{ $this->variant->retail_price?->format() }}</td>
                                    <td class="fs-10 text-body-tertiary">{{ $this->variant->sku }}</td>
                                    <td></td>
                                </tr>
                                @forelse ($this->units as $unit)
                                    <tr wire:key="pk-unit-{{ $unit->id }}">
                                        <td class="fw-semibold fs-9">{{ $unit->name }}</td>
                                        <td class="text-end fs-9">×{{ $unit->multiplier }}</td>
                                        <td class="text-end fs-9">{{ $unit->unitPrice()->format() }}</td>
                                        <td class="fs-10 text-body-tertiary">{{ $unit->barcode ?: '—' }}</td>
                                        <td class="text-end">
                                            <div class="table-actions justify-content-end">
                                                <button class="btn btn-sm btn-phoenix-secondary" wire:click="edit({{ $unit->id }})"><span class="fas fa-pen"></span></button>
                                                <button class="btn btn-sm btn-phoenix-danger" wire:click="delete({{ $unit->id }})" wire:confirm="Remove this packaging unit?"><span class="fas fa-xmark"></span></button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-body-tertiary py-3 fs-9">No extra packaging yet — sold in base units only.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.card>
        </div>
    </div>
</div>
