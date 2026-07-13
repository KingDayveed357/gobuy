<div wire:key="transfer-stock">
    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <x-admin.card title="Move stock">
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-5">
                        <label class="form-label">From</label>
                        <select class="form-select" wire:model.live="fromId">
                            <option value="">Choose…</option>
                            @foreach ($this->locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-2 text-center text-body-tertiary"><span class="fas fa-arrow-right-long fs-5"></span></div>
                    <div class="col-5">
                        <label class="form-label">To</label>
                        <select class="form-select" wire:model.live="toId">
                            <option value="">Choose…</option>
                            @foreach ($this->locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                @error('fromId')<div class="text-danger fs-10 mb-2">{{ $message }}</div>@enderror

                <div class="mb-3">
                    <x-admin.product-picker scope="transfer-stock" on-select="addVariant" :in-stock="true" placeholder="Search products to move…" />
                </div>

                @if (empty($this->rows))
                    <x-admin.empty-state icon="fa-dolly" text="No items yet — search above to add stock to move." />
                @else
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-3">
                            <thead><tr><th>Item</th><th class="text-end">At source</th><th style="width:130px;">Move</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($this->rows as $row)
                                    <tr wire:key="tr-line-{{ $row['variant_id'] }}">
                                        <td><span class="fw-semibold d-block fs-9">{{ $row['name'] }}</span><span class="fs-10 text-body-tertiary">SKU {{ $row['sku'] }}</span></td>
                                        <td class="text-end {{ $row['quantity'] > $row['available'] ? 'text-danger fw-bold' : '' }}">{{ $row['available'] }}</td>
                                        <td><input type="number" min="1" class="form-control form-control-sm text-center" value="{{ $row['quantity'] }}" wire:change="setQuantity({{ $row['variant_id'] }}, $event.target.value)"></td>
                                        <td class="text-end"><button class="btn btn-sm btn-phoenix-danger" wire:click="removeLine({{ $row['variant_id'] }})"><span class="fas fa-xmark"></span></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3"><label class="form-label">Note <span class="text-body-tertiary fs-10">(optional)</span></label><input class="form-control" wire:model="note" placeholder="e.g. Restocking the shop from home"></div>
                    <button class="btn btn-primary w-100" wire:click="submit" wire:loading.attr="disabled"><span class="fas fa-dolly me-2"></span>Transfer stock</button>
                @endif
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-5">
            <x-admin.card title="Recent transfers" flush>
                <ul class="list-group list-group-flush">
                    @forelse ($recent as $transfer)
                        <li class="list-group-item px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold fs-9">{{ $transfer->from?->name }} <span class="fas fa-arrow-right-long text-body-tertiary mx-1"></span> {{ $transfer->to?->name }}</span>
                                <span class="badge badge-phoenix badge-phoenix-secondary">{{ $transfer->totalUnits() }} units</span>
                            </div>
                            <span class="fs-10 text-body-tertiary">{{ $transfer->created_at->format('M j, g:i A') }} · {{ $transfer->items->count() }} item{{ $transfer->items->count() === 1 ? '' : 's' }}@if ($transfer->createdBy) · {{ $transfer->createdBy->name }}@endif</span>
                        </li>
                    @empty
                        <li class="list-group-item px-3"><x-admin.empty-state icon="fa-dolly" text="No transfers yet." /></li>
                    @endforelse
                </ul>
            </x-admin.card>
        </div>
    </div>
</div>
