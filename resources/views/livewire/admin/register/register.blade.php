<div wire:key="register">
    @php($session = $this->session)

    @if (! $session)
        {{-- ─── Open the day ─────────────────────────────────────────── --}}
        <div class="row justify-content-center">
            <div class="col-12 col-md-7 col-lg-5">
                <x-admin.card>
                    <div class="text-center mb-4">
                        <span class="fas fa-cash-register text-primary" style="font-size:2.5rem;"></span>
                        <h4 class="mt-3 mb-1">Open the register</h4>
                        <p class="text-body-tertiary mb-0">Start the business day. Count the cash already in the drawer as your opening float.</p>
                    </div>
                    <label class="form-label">Opening cash float (₦)</label>
                    <input type="number" min="0" step="0.01" class="form-control form-control-lg mb-3" wire:model="openingFloat" placeholder="0.00">
                    <button class="btn btn-primary btn-lg w-100" wire:click="open" wire:loading.attr="disabled">
                        <span class="fas fa-play me-2"></span>Open register
                    </button>
                </x-admin.card>
            </div>
        </div>
    @else
        {{-- ─── Live session + close ─────────────────────────────────── --}}
        @php($sales = $session->windowSales())
        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <x-admin.card title="Current session">
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between px-0"><span class="text-body-tertiary">Opened</span><span class="fw-semibold">{{ $session->opened_at->format('M j, g:i A') }}</span></li>
                        <li class="list-group-item d-flex justify-content-between px-0"><span class="text-body-tertiary">By</span><span class="fw-semibold">{{ $session->openedBy?->name ?? '—' }}</span></li>
                        <li class="list-group-item d-flex justify-content-between px-0"><span class="text-body-tertiary">Opening float</span><span class="fw-semibold">{{ $session->opening_float->format() }}</span></li>
                        <li class="list-group-item d-flex justify-content-between px-0"><span class="text-body-tertiary">Sales so far</span><span class="fw-semibold">{{ $sales['count'] }}</span></li>
                    </ul>
                    <h6 class="text-body-tertiary text-uppercase fs-10 mb-2">Taken today</h6>
                    <div class="d-flex justify-content-between fs-9 py-1"><span>Cash</span><span class="fw-semibold">{{ $sales['cash']->format() }}</span></div>
                    <div class="d-flex justify-content-between fs-9 py-1"><span>POS terminal</span><span class="fw-semibold">{{ $sales['pos']->format() }}</span></div>
                    <div class="d-flex justify-content-between fs-9 py-1"><span>Bank transfer</span><span class="fw-semibold">{{ $sales['transfer']->format() }}</span></div>
                </x-admin.card>
            </div>

            <div class="col-12 col-lg-7">
                <x-admin.card title="Close the day" subtitle="Count each tender and check it against what the day expected.">
                    <div class="table-responsive">
                        <table class="table align-middle mb-3">
                            <thead><tr><th>Tender</th><th class="text-end">Expected</th><th style="width:170px;">Counted</th><th class="text-end">Difference</th></tr></thead>
                            <tbody>
                                @foreach ($this->preview as $key => $row)
                                    @php($v = $row['variance']->kobo)
                                    <tr>
                                        <td class="fw-semibold fs-9">{{ $row['label'] }}</td>
                                        <td class="text-end">{{ $row['expected']->format() }}</td>
                                        <td>
                                            <input type="number" min="0" step="0.01" class="form-control form-control-sm text-end"
                                                   wire:model.live.debounce.300ms="counted{{ ucfirst($key === 'transfer' ? 'Transfer' : $key) }}" placeholder="0.00">
                                        </td>
                                        <td class="text-end">
                                            @if ($v === 0)
                                                <span class="badge badge-phoenix badge-phoenix-success">Balanced</span>
                                            @else
                                                <span class="badge badge-phoenix badge-phoenix-{{ $v > 0 ? 'warning' : 'danger' }}">{{ $v > 0 ? '+' : '−' }}{{ \App\Support\Money::fromKobo(abs($v))->format() }} {{ $v > 0 ? 'over' : 'short' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note <span class="text-body-tertiary fs-10">(optional — explain any variance)</span></label>
                        <input class="form-control" wire:model="note" placeholder="e.g. ₦500 given as change and not recovered">
                    </div>
                    <button class="btn btn-success w-100" wire:click="close" wire:loading.attr="disabled">
                        <span class="fas fa-lock me-2"></span>Close &amp; reconcile the day
                    </button>
                </x-admin.card>
            </div>
        </div>
    @endif

    {{-- ─── History ──────────────────────────────────────────────────── --}}
    @if ($history->isNotEmpty())
        <x-admin.card title="Recent days" flush class="mt-4">
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead><tr><th>Closed</th><th>By</th><th class="text-end">Sales</th><th class="text-end">Cash variance</th><th class="text-end">Total variance</th></tr></thead>
                    <tbody>
                        @foreach ($history as $past)
                            @php($var = $past->variance())
                            @php($totalVar = $var['cash']->kobo + $var['pos']->kobo + $var['transfer']->kobo)
                            <tr>
                                <td class="fs-9">{{ $past->closed_at->format('M j, g:i A') }}</td>
                                <td class="fs-9 text-body-tertiary">{{ $past->closedBy?->name ?? '—' }}</td>
                                <td class="text-end fs-9">{{ $past->expected_cash?->plus($past->expected_pos ?? \App\Support\Money::zero())->plus($past->expected_transfer ?? \App\Support\Money::zero())?->format() ?? '—' }}</td>
                                <td class="text-end fs-9">{{ $var['cash']->kobo === 0 ? '✓' : ($var['cash']->kobo > 0 ? '+' : '−').\App\Support\Money::fromKobo(abs($var['cash']->kobo))->format() }}</td>
                                <td class="text-end">
                                    <span class="badge badge-phoenix badge-phoenix-{{ $totalVar === 0 ? 'success' : ($totalVar > 0 ? 'warning' : 'danger') }}">
                                        {{ $totalVar === 0 ? 'Balanced' : (($totalVar > 0 ? '+' : '−').\App\Support\Money::fromKobo(abs($totalVar))->format()) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    @endif
</div>
