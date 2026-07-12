<div wire:key="receive-goods-{{ $order->id }}">
    <x-admin.card title="Receive goods" subtitle="Enter what actually arrived — trim any line that was short-delivered. Received stock lands at {{ $order->location?->name }}.">
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-3">
                <thead><tr><th>Item</th><th class="text-end">Outstanding</th><th style="width:140px;">Receive now</th></tr></thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr wire:key="rcv-{{ $item->id }}">
                            <td><span class="fw-semibold d-block fs-9">{{ $item->variant?->product?->name }}@if ($item->variant && ! $item->variant->is_default) — {{ $item->variant->label() }}@endif</span><span class="fs-10 text-body-tertiary">SKU {{ $item->variant?->sku }}</span></td>
                            <td class="text-end {{ $item->outstanding() === 0 ? 'text-body-tertiary' : 'fw-semibold' }}">{{ $item->outstanding() }}</td>
                            <td>
                                @if ($item->outstanding() === 0)
                                    <span class="badge badge-phoenix badge-phoenix-success w-100">Complete</span>
                                @else
                                    <input type="number" min="0" max="{{ $item->outstanding() }}" class="form-control form-control-sm text-center" wire:model="receive.{{ $item->id }}">
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary" wire:click="submit" wire:loading.attr="disabled"><span class="fas fa-box-open me-2"></span>Receive into stock</button>
    </x-admin.card>
</div>
