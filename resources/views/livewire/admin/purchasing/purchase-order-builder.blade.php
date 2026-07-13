<div wire:key="po-builder">
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <x-admin.card title="Items">
                <div class="mb-3">
                    <x-admin.product-picker scope="purchase-order" on-select="addVariant" placeholder="Search products to order…" />
                </div>

                @if (empty($this->rows))
                    <x-admin.empty-state icon="fa-file-invoice" text="No items yet — search above to add what you're ordering." />
                @else
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <thead><tr><th>Item</th><th style="width:110px;">Qty</th><th style="width:150px;">Unit cost (₦)</th><th class="text-end">Line cost</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($this->rows as $row)
                                    <tr wire:key="po-line-{{ $row['variant_id'] }}">
                                        <td><span class="fw-semibold d-block fs-9">{{ $row['name'] }}</span><span class="fs-10 text-body-tertiary">SKU {{ $row['sku'] }}</span></td>
                                        <td><input type="number" min="1" class="form-control form-control-sm text-center" wire:model.live="lines.{{ $row['variant_id'] }}.quantity"></td>
                                        <td><input type="number" min="0" step="0.01" class="form-control form-control-sm text-end" wire:model.live.debounce.400ms="lines.{{ $row['variant_id'] }}.unit_cost" placeholder="0.00"></td>
                                        <td class="text-end fw-semibold">{{ $row['line_cost']->format() }}</td>
                                        <td class="text-end"><button class="btn btn-sm btn-phoenix-danger" wire:click="removeLine({{ $row['variant_id'] }})"><span class="fas fa-xmark"></span></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-top"><td colspan="3" class="text-end fw-semibold">Total</td><td class="text-end fw-bold">{{ $this->total->format() }}</td><td></td></tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-4">
            <x-admin.card title="Order details">
                <div class="mb-3">
                    <label class="form-label">Supplier</label>
                    <select class="form-select" wire:model="supplierId">
                        <option value="">— (none)</option>
                        @foreach ($this->suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                    @if ($this->suppliers->isEmpty())<div class="fs-10 text-body-tertiary mt-1">No suppliers yet — <a href="{{ route('admin.suppliers.index') }}">add one</a>.</div>@endif
                </div>
                <div class="mb-3">
                    <label class="form-label">Deliver to</label>
                    <select class="form-select @error('locationId') is-invalid @enderror" wire:model="locationId">
                        <option value="">Choose…</option>
                        @foreach ($this->locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                    </select>
                    @error('locationId')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Note <span class="text-body-tertiary fs-10">(optional)</span></label>
                    <textarea class="form-control" wire:model="note" rows="2" placeholder="e.g. Monthly restock"></textarea>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" wire:click="save(true)" wire:loading.attr="disabled" @disabled(empty($this->rows))><span class="fas fa-paper-plane me-2"></span>Place order</button>
                    <button class="btn btn-phoenix-secondary" wire:click="save(false)" wire:loading.attr="disabled" @disabled(empty($this->rows))>Save as draft</button>
                </div>
            </x-admin.card>
        </div>
    </div>
</div>
