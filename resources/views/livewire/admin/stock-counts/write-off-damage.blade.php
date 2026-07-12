<div wire:key="write-off-damage">
    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <x-admin.card title="Write off damage">
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label">Location</label>
                        <select class="form-select" wire:model="locationId">
                            <option value="">Choose…</option>
                            @foreach ($this->locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                @if (! $this->variant)
                    <div class="position-relative mb-2">
                        <label class="form-label">Item</label>
                        <input type="text" class="form-control @error('variantId') is-invalid @enderror" wire:model.live.debounce.250ms="search" placeholder="Search a damaged item…" autocomplete="off">
                        @error('variantId')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @if (! empty($this->results) && count($this->results))
                            <div class="list-group position-absolute w-100 shadow-sm" style="z-index:30; max-height:280px; overflow:auto;">
                                @foreach ($this->results as $variant)
                                    <button type="button" class="list-group-item list-group-item-action py-2" wire:click="choose({{ $variant->id }})" wire:key="dm-res-{{ $variant->id }}">
                                        <span class="d-block fw-semibold text-truncate fs-9">{{ $variant->is_default ? $variant->product->name : $variant->product->name.' — '.$variant->label() }}</span><span class="fs-10 text-body-tertiary">SKU {{ $variant->sku }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="d-flex justify-content-between align-items-center p-2 rounded bg-body-secondary mb-3">
                        <span><span class="fw-semibold d-block fs-9">{{ $this->variant->is_default ? $this->variant->product->name : $this->variant->product->name.' — '.$this->variant->label() }}</span><span class="fs-10 text-body-tertiary">SKU {{ $this->variant->sku }}</span></span>
                        <button class="btn btn-sm btn-phoenix-secondary" wire:click="clearVariant"><span class="fas fa-xmark"></span></button>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4"><label class="form-label">Quantity</label><input type="number" min="1" class="form-control @error('quantity') is-invalid @enderror" wire:model="quantity">@error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-8"><label class="form-label">Reason <span class="text-body-tertiary fs-10">(optional)</span></label><input class="form-control" wire:model="reason" placeholder="e.g. Broken in transit"></div>
                    </div>
                    <button class="btn btn-danger" wire:click="submit" wire:loading.attr="disabled"><span class="fas fa-trash-can me-2"></span>Write off</button>
                @endif
            </x-admin.card>
        </div>

        <div class="col-12 col-lg-5">
            <x-admin.card title="Recent write-offs" flush>
                <ul class="list-group list-group-flush">
                    @forelse ($recent as $movement)
                        <li class="list-group-item px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold fs-9">{{ $movement->variant?->product?->name ?? '—' }}</span>
                                <span class="badge badge-phoenix badge-phoenix-danger">{{ $movement->quantity }}</span>
                            </div>
                            <span class="fs-10 text-body-tertiary">{{ $movement->created_at->format('M j, g:i A') }} · {{ $movement->location?->name }}@if ($movement->note) · {{ $movement->note }}@endif</span>
                        </li>
                    @empty
                        <li class="list-group-item px-3"><x-admin.empty-state icon="fa-trash-can" text="No write-offs yet." /></li>
                    @endforelse
                </ul>
            </x-admin.card>
        </div>
    </div>
</div>
