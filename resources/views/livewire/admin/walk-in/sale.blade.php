<div class="row g-4" wire:key="walk-in-sale">
    {{-- ─── Products + basket ─────────────────────────────────────────── --}}
    <div class="col-12 col-lg-7">
        <x-admin.card title="Add items">
            <div class="mb-3">
                <x-admin.product-picker scope="walk-in" on-select="addVariant" :in-stock="true" :wholesale="true"
                    placeholder="Search products by name, SKU or brand…" :autofocus="true" />
            </div>

            @php($cart = $this->cart)
            @if (empty($cart['rows']))
                <x-admin.empty-state icon="fa-basket-shopping" text="No items yet — search above to start a sale." />
            @else
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0">
                        <thead><tr><th>Item</th><th class="text-center" style="width:150px;">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($cart['rows'] as $row)
                                <tr wire:key="line-{{ $row['variant_id'] }}">
                                    <td>
                                        <span class="fw-semibold d-block fs-9">{{ $row['name'] }}</span>
                                        <span class="fs-10 text-body-tertiary">SKU {{ $row['sku'] }}</span>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm" style="width:140px; margin:0 auto;">
                                            <button class="btn btn-phoenix-secondary" type="button" wire:click="setQuantity({{ $row['variant_id'] }}, {{ $row['quantity'] - 1 }})"><span class="fas fa-minus"></span></button>
                                            <input type="number" min="1" max="{{ $row['stock'] }}" class="form-control text-center" value="{{ $row['quantity'] }}"
                                                   wire:change="setQuantity({{ $row['variant_id'] }}, $event.target.value)">
                                            <button class="btn btn-phoenix-secondary" type="button" wire:click="setQuantity({{ $row['variant_id'] }}, {{ $row['quantity'] + 1 }})" @disabled($row['quantity'] >= $row['stock'])><span class="fas fa-plus"></span></button>
                                        </div>
                                    </td>
                                    <td class="text-end">{{ $row['unit']->format() }}</td>
                                    <td class="text-end fw-semibold">{{ $row['line_total']->format() }}</td>
                                    <td class="text-end"><button class="btn btn-sm btn-phoenix-danger" wire:click="removeLine({{ $row['variant_id'] }})"><span class="fas fa-xmark"></span></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-admin.card>
    </div>

    {{-- ─── Checkout ──────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-5">
        @if ($completed)
            <x-admin.card>
                <div class="text-center py-4">
                    <span class="fas fa-circle-check text-success" style="font-size:3rem;"></span>
                    <h4 class="mt-3 mb-1">Sale recorded</h4>
                    <p class="text-body-tertiary mb-3">{{ $completed['number'] }}</p>
                    <div class="fs-5 fw-bold">{{ $completed['total'] }}</div>
                    <p class="text-body-tertiary fs-9">Paid by {{ $completed['method'] }}</p>
                    <button class="btn btn-primary mt-2" wire:click="newSale"><span class="fas fa-plus me-1"></span>New sale</button>
                </div>
            </x-admin.card>
        @else
            <x-admin.card title="Checkout">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-body-tertiary">{{ $cart['count'] }} item{{ $cart['count'] === 1 ? '' : 's' }}</span>
                    <span class="fs-4 fw-bolder">{{ $cart['total']->format() }}</span>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="wholesaleToggle" wire:model.live="wholesale">
                    <label class="form-check-label" for="wholesaleToggle">Wholesale pricing <span class="fs-10 text-body-tertiary">(tier prices for bulk quantities)</span></label>
                </div>

                <label class="form-label">Payment method</label>
                <div class="btn-group w-100 mb-3" role="group">
                    @foreach ($methods as $m)
                        <input type="radio" class="btn-check" name="pm" id="pm-{{ $m->value }}" value="{{ $m->value }}" wire:model="paymentMethod" @checked($paymentMethod === $m->value)>
                        <label class="btn btn-phoenix-secondary" for="pm-{{ $m->value }}">{{ $m->label() }}</label>
                    @endforeach
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-7"><label class="form-label">Customer name <span class="text-body-tertiary fs-10">(optional)</span></label><input class="form-control form-control-sm" wire:model="customerName" placeholder="Walk-in"></div>
                    <div class="col-5"><label class="form-label">Phone</label><input class="form-control form-control-sm" wire:model="customerPhone"></div>
                </div>

                <button class="btn btn-success w-100 btn-lg" wire:click="complete" wire:loading.attr="disabled" @disabled(empty($cart['rows']))>
                    <span wire:loading.remove wire:target="complete"><span class="fas fa-cash-register me-2"></span>Complete sale · {{ $cart['total']->format() }}</span>
                    <span wire:loading wire:target="complete"><span class="spinner-border spinner-border-sm me-2"></span>Recording…</span>
                </button>
            </x-admin.card>
        @endif
    </div>
</div>
