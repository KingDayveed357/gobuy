<div wire:key="record-stock-count">
    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <x-admin.card title="Record a count">
                <div class="mb-3" style="max-width:280px;">
                    <label class="form-label">Location</label>
                    <select class="form-select" wire:model.live="locationId">
                        <option value="">Choose…</option>
                        @foreach ($this->locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <x-admin.product-picker scope="stock-count" on-select="addVariant" placeholder="Search items to count…" />
                </div>

                @if (empty($this->rows))
                    <x-admin.empty-state icon="fa-clipboard-list" text="No items yet — search above to start counting." />
                @else
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-3">
                            <thead><tr><th>Item</th><th class="text-end">System</th><th style="width:120px;">Counted</th><th class="text-end">Variance</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($this->rows as $row)
                                    <tr wire:key="sc-line-{{ $row['variant_id'] }}">
                                        <td><span class="fw-semibold d-block fs-9">{{ $row['name'] }}</span><span class="fs-10 text-body-tertiary">SKU {{ $row['sku'] }}</span></td>
                                        <td class="text-end fs-9">{{ $row['expected'] }}</td>
                                        <td><input type="number" min="0" class="form-control form-control-sm text-center" wire:model.live="counts.{{ $row['variant_id'] }}" placeholder="—"></td>
                                        <td class="text-end fw-semibold">
                                            @if ($row['variance'] === null)<span class="text-body-tertiary">—</span>
                                            @elseif ($row['variance'] === 0)<span class="text-body-tertiary">0</span>
                                            @elseif ($row['variance'] > 0)<span class="text-success">+{{ $row['variance'] }}</span>
                                            @else<span class="text-danger">{{ $row['variance'] }}</span>@endif
                                        </td>
                                        <td class="text-end"><button class="btn btn-sm btn-phoenix-danger" wire:click="removeLine({{ $row['variant_id'] }})"><span class="fas fa-xmark"></span></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3"><label class="form-label">Note <span class="text-body-tertiary fs-10">(optional)</span></label><input class="form-control" wire:model="note" placeholder="e.g. Month-end count"></div>
                    <button class="btn btn-primary" wire:click="submit" wire:loading.attr="disabled"><span class="fas fa-clipboard-check me-2"></span>Record count &amp; reconcile</button>
                @endif
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-5">
            <x-admin.card title="Recent counts" flush>
                <ul class="list-group list-group-flush">
                    @forelse ($recent as $count)
                        <li class="list-group-item px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold fs-9">{{ $count->location?->name }}</span>
                                @php($net = $count->netVariance())
                                <span class="badge badge-phoenix badge-phoenix-{{ $net === 0 ? 'secondary' : ($net > 0 ? 'success' : 'danger') }}">{{ $net > 0 ? '+' : '' }}{{ $net }}</span>
                            </div>
                            <span class="fs-10 text-body-tertiary">{{ $count->counted_at?->format('M j, g:i A') }} · {{ $count->items->count() }} item{{ $count->items->count() === 1 ? '' : 's' }}@if ($count->createdBy) · {{ $count->createdBy->name }}@endif</span>
                        </li>
                    @empty
                        <li class="list-group-item px-3"><x-admin.empty-state icon="fa-clipboard-list" text="No counts yet." /></li>
                    @endforelse
                </ul>
            </x-admin.card>
        </div>
    </div>
</div>
