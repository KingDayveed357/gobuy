@extends('admin.layouts.app')

@section('title', 'Bulk quantity requests — gobuy admin')
@section('page-title', 'Bulk requests')

@section('content')
    <x-admin.page-header title="Bulk quantity requests" subtitle="Wholesale & large-order leads captured from product pages" />

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm fs-9 mb-0">
                    <thead>
                        <tr class="text-body-tertiary">
                            <th class="ps-3">Requested</th>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th>Contact</th>
                            <th>Note</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $req)
                            <tr>
                                <td class="ps-3 text-body-tertiary">{{ $req->created_at->format('M j, Y') }}</td>
                                <td>
                                    <span class="fw-semibold text-body-emphasis">{{ $req->product?->name ?? '—' }}</span>
                                    @if ($req->variant)<br><span class="fs-10 text-body-tertiary">{{ $req->variant->label() }}</span>@endif
                                </td>
                                <td class="text-center fw-bold">{{ number_format($req->quantity) }}</td>
                                <td>
                                    {{ $req->name }}<br>
                                    <span class="fs-10 text-body-tertiary">{{ $req->email }}@if ($req->phone) · {{ $req->phone }}@endif</span>
                                </td>
                                <td class="text-body-tertiary" style="max-width: 16rem;">{{ \Illuminate\Support\Str::limit($req->note, 80) ?: '—' }}</td>
                                <td>
                                    <span class="badge badge-phoenix {{ $req->status === 'closed' ? 'badge-phoenix-secondary' : ($req->status === 'contacted' ? 'badge-phoenix-info' : 'badge-phoenix-warning') }}">{{ ucfirst($req->status) }}</span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        @foreach (['new' => 'Reopen', 'contacted' => 'Contacted', 'closed' => 'Close'] as $value => $label)
                                            @if ($req->status !== $value)
                                                <form method="POST" action="{{ route('admin.bulk-requests.status', $req) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $value }}">
                                                    <button type="submit" class="btn btn-phoenix-secondary btn-sm">{{ $label }}</button>
                                                </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-body-tertiary py-5">No bulk requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
