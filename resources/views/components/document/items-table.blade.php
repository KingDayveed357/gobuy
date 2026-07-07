@props([
    'items',           // Illuminate\Support\Collection — order items
    'showSku'  => true, // bool — show SKU / product code column
    'showImage'=> false, // bool — show product thumbnail (screen only, hidden on print)
    'columns'  => [],   // array<string> — override visible column set
])

{{--
    Items Table Component
    ─────────────────────
    The primary line-items table used by all order documents.
    thead has display:table-header-group so it repeats on every
    printed page — critical for long invoices / packing slips.

    The component is intentionally flexible:
    - `showSku` adds the SKU/code column
    - `showImage` adds a thumbnail column (hidden on print via no-print)
    - Override `columns` for fully custom headers

    Usage:
        <x-document.items-table :items="$order->items" />
        <x-document.items-table :items="$lines" :show-sku="false" />
--}}
<div class="doc-table-wrapper">
    <table class="doc-table" role="table">
        <thead>
            <tr>
                <th class="col-w-num">#</th>
                @if ($showSku)
                    <th class="col-w-sku">SKU</th>
                @endif
                <th class="col-w-auto">Item</th>
                <th class="col-w-qty text-center">Qty</th>
                <th class="col-w-price text-right">Unit Price</th>
                <th class="col-w-total text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $item)
                @php
                    /* Support both OrderItem models and proforma line arrays */
                    $isModel   = is_object($item) && method_exists($item, 'getAttribute');
                    $name      = $isModel ? $item->name       : ($item['item']->variant->product->name ?? 'Item');
                    $sku       = $isModel ? ($item->sku ?? $item->variant?->sku ?? null) : ($item['item']->variant->sku ?? null);
                    $variant   = $isModel ? null              : ($item['item']->variant->attributeLabel() ?? null);
                    $qty       = $isModel ? $item->quantity   : $item['quantity'];
                    $unitPrice = $isModel ? $item->unit_price : $item['price'];
                    $lineTotal = $isModel ? $item->line_total : $item['lineTotal'];
                @endphp
                <tr>
                    <td class="text-center text-secondary text-xs">{{ $loop->iteration }}</td>
                    @if ($showSku)
                        <td class="text-xs text-secondary">{{ $sku ?? '—' }}</td>
                    @endif
                    <td>
                        <span class="cell-item-name">{{ $name }}</span>
                        @if ($variant)
                            <span class="cell-item-variant">{{ $variant }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $qty }}</td>
                    <td class="text-right">{{ money($unitPrice) }}</td>
                    <td class="text-right font-semibold">{{ money($lineTotal) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showSku ? 6 : 5 }}" class="text-center text-secondary" style="padding: 20px 10px;">
                        No items.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
