@extends('admin.layouts.app')

@section('title', 'Inventory — Quintessential Mart admin')

@section('content')
    <x-admin.page-header title="Inventory" subtitle="Stock levels, reservations and audited adjustments">
        <x-slot:actions>
            <a href="{{ route('admin.inventory.import.create') }}" class="btn btn-phoenix-secondary"><span class="fas fa-file-arrow-up me-2"></span>Bulk import</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card flush>
        <div class="card-header admin-card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <ul class="nav nav-pills gap-2 fs-9">
                @php($tabs = ['' => 'All ('.$counts['all'].')', 'low' => 'Low stock ('.$counts['low'].')', 'out' => 'Out of stock ('.$counts['out'].')'])
                @foreach ($tabs as $key => $label)
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3 {{ $filter === $key ? 'active' : 'bg-body-secondary text-body' }}"
                           href="{{ route('admin.inventory.index', array_filter(['filter' => $key, 'q' => request('q')])) }}">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
            <form method="GET" action="{{ route('admin.inventory.index') }}" class="d-flex gap-2">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input class="form-control form-control-sm" type="search" name="q" value="{{ request('q') }}" placeholder="Search SKU or product…" style="min-width: 220px;">
                <button class="btn btn-sm btn-phoenix-primary" type="submit"><span class="fas fa-search"></span></button>
            </form>
        </div>

        <div class="table-responsive position-relative" data-admin-table>
            <table class="table admin-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-end">On hand</th>
                        <th class="text-end">Reserved</th>
                        <th class="text-end">Available</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($variants as $variant)
                        @php($held = (int) ($reserved[$variant->id] ?? 0))
                        @php($available = max(0, $variant->stock - $held))
                        <tr>
                            <td>
                                <a href="{{ route('admin.products.edit', $variant->product) }}" class="fw-semibold text-body-emphasis text-decoration-none line-clamp-1">{{ $variant->product->name ?? '—' }}</a>
                                @unless ($variant->is_default)<span class="fs-10 text-body-tertiary">{{ $variant->name }}</span>@endunless
                            </td>
                            <td class="text-body-tertiary">{{ $variant->sku }}</td>
                            <td class="text-end fw-semibold">{{ number_format($variant->stock) }}</td>
                            <td class="text-end {{ $held > 0 ? 'text-warning' : 'text-body-tertiary' }}">{{ number_format($held) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($available) }}</td>
                            <td>
                                @if ($variant->stock <= 0)
                                    <span class="badge badge-phoenix badge-phoenix-danger">Out of stock</span>
                                @elseif ($variant->isLowStock())
                                    <span class="badge badge-phoenix badge-phoenix-warning">Low ({{ $variant->low_stock_threshold }})</span>
                                @else
                                    <span class="badge badge-phoenix badge-phoenix-success">In stock</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-phoenix-primary"
                                        data-bs-toggle="modal" data-bs-target="#adjustModal"
                                        data-action="{{ route('admin.inventory.adjust', $variant) }}"
                                        data-sku="{{ $variant->sku }}"
                                        data-stock="{{ $variant->stock }}">
                                    <span class="fas fa-sliders me-1"></span>Adjust
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-admin.empty-state title="No variants found" text="Try a different search or filter." /></td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="admin-table-loading" aria-hidden="true">
                <x-admin.skeleton type="table" :rows="8" :cols="7" />
            </div>
        </div>

        @push('scripts')
            <script src="{{ asset('theme/js/table-loading.js') }}" defer></script>
        @endpush

        @if ($variants->hasPages())
            <div class="p-3">{{ $variants->links() }}</div>
        @endif
    </x-admin.card>

    {{-- Adjust stock modal --}}
    <div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="adjustForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Adjust stock <span class="text-body-tertiary fs-9" id="adjustSku"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="fs-9 text-body-tertiary mb-3">Current on-hand: <span class="fw-semibold text-body" id="adjustCurrent">0</span></p>
                        <div class="mb-3">
                            <label class="form-label">Action</label>
                            <select class="form-select" name="mode" id="adjustMode">
                                <option value="adjust">Adjust by (＋ restock / − shrinkage)</option>
                                <option value="set">Set to exact amount</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="adjustAmountLabel">Quantity change</label>
                            <input class="form-control" type="number" name="amount" id="adjustAmount" value="0" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Reason <span class="text-body-tertiary">— optional</span></label>
                            <input class="form-control" type="text" name="reason" placeholder="e.g. Canton Fair restock, damaged units">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('adjustModal');
            if (!modal) { return; }
            var form = document.getElementById('adjustForm');
            var mode = document.getElementById('adjustMode');
            var label = document.getElementById('adjustAmountLabel');
            var amount = document.getElementById('adjustAmount');

            modal.addEventListener('show.bs.modal', function (event) {
                var btn = event.relatedTarget;
                form.action = btn.getAttribute('data-action');
                document.getElementById('adjustSku').textContent = btn.getAttribute('data-sku');
                document.getElementById('adjustCurrent').textContent = btn.getAttribute('data-stock');
                mode.value = 'adjust';
                amount.value = 0;
                label.textContent = 'Quantity change';
            });

            mode.addEventListener('change', function () {
                label.textContent = this.value === 'set' ? 'New on-hand amount' : 'Quantity change';
                amount.value = 0;
            });
        })();
    </script>
@endsection
