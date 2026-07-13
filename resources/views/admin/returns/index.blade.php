@extends('admin.layouts.app')

@section('title', 'Returns — Quintessential Mart admin')
@section('page-title', 'Returns')

@section('content')
    <x-admin.page-header title="Returns" subtitle="{{ $returns->total() }} return request(s)">
        <x-slot:actions>
            <a href="{{ route('admin.returns.export', request()->query()) }}" class="btn btn-phoenix-secondary"><span class="fas fa-file-csv me-2"></span>Export CSV</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (($kpis['failed_settlements'] ?? 0) > 0)
        <div class="alert alert-subtle-danger d-flex align-items-center">
            <span class="fas fa-triangle-exclamation me-2"></span>
            {{ $kpis['failed_settlements'] }} return(s) have a failed gateway refund awaiting retry.
        </div>
    @endif

    <div class="row g-3 mb-4">
        @php($cards = [
            ['label' => 'Open returns', 'value' => $kpis['open'], 'icon' => 'fa-inbox'],
            ['label' => 'Last '.$kpis['window_days'].' days', 'value' => $kpis['total'], 'icon' => 'fa-rotate-left'],
            ['label' => 'Return rate', 'value' => $kpis['return_rate'].'%', 'icon' => 'fa-percent'],
            ['label' => 'Auto-approved', 'value' => $kpis['auto_approval_rate'].'%', 'icon' => 'fa-bolt'],
            ['label' => 'Refunded ('.$kpis['window_days'].'d)', 'value' => money($kpis['refunded_value']), 'icon' => 'fa-money-bill-wave'],
            ['label' => 'Avg approval', 'value' => $kpis['avg_approval_hours'] !== null ? $kpis['avg_approval_hours'].'h' : '—', 'icon' => 'fa-clock'],
        ])
        @foreach ($cards as $card)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card h-100"><div class="card-body py-3">
                    <p class="fs-9 text-body-tertiary mb-1"><span class="fas {{ $card['icon'] }} me-1"></span>{{ $card['label'] }}</p>
                    <h4 class="mb-0">{{ $card['value'] }}</h4>
                </div></div>
            </div>
        @endforeach
    </div>

    <x-admin.table
        :cols="[
            ['label' => 'Reference'],
            ['label' => 'Order'],
            ['label' => 'Items', 'align' => 'end'],
            ['label' => 'Reason'],
            ['label' => 'Risk'],
            ['label' => 'Status'],
            ['label' => '', 'align' => 'end'],
        ]"
        :empty="$returns->isEmpty()"
        empty-icon="fa-rotate-left"
        empty-text="No return requests yet."
    >
        <x-slot:toolbar>
            <form method="GET" class="admin-toolbar mb-0 w-100 d-flex gap-2">
                <input class="form-control form-control-sm" style="max-width:280px" type="search" name="q" value="{{ request('q') }}" placeholder="Search reference or order">
                <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-phoenix-secondary" type="submit">Search</button>
            </form>
        </x-slot:toolbar>

        @foreach ($returns as $return)
            <tr>
                <td class="fw-semibold text-body-emphasis">{{ $return->reference }}</td>
                <td>{{ $return->order?->order_number }}</td>
                <td class="text-end">{{ $return->items->sum('quantity') }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $return->reason_code)) }}</td>
                <td>
                    @if ($return->risk_score !== null)
                        <span class="badge badge-phoenix badge-phoenix-{{ $return->risk_score >= 70 ? 'danger' : ($return->risk_score >= 40 ? 'warning' : 'success') }}">{{ $return->risk_score }}</span>
                    @else
                        <span class="text-body-tertiary fs-10">—</span>
                    @endif
                </td>
                <td><span class="badge badge-phoenix badge-phoenix-{{ $return->status->isSettled() ? 'success' : ($return->status->isOpen() ? 'warning' : 'secondary') }}">{{ $return->status->label() }}</span></td>
                <td class="text-end"><a href="{{ route('admin.returns.show', $return) }}" class="btn btn-sm btn-phoenix-secondary">Review</a></td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $returns->links() }}</div>
@endsection
