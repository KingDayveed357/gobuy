@extends('layouts.account')

@section('title', 'My Returns — gobuy')

@php
    $pageTitle = 'Returns';
@endphp

@section('account_content')
    <div class="mb-4">
        <h3 class="mb-0 text-body-emphasis">My Returns</h3>
        <p class="text-body-tertiary">Track your return requests and their statuses.</p>
    </div>

    @if ($returns->isEmpty())
        <div class="card border-0 shadow-sm"><div class="card-body text-center py-6">
            <span class="fas fa-box-open fs-1 text-body-tertiary mb-3"></span>
            <h5 class="text-body-emphasis mb-2">No returns yet</h5>
            <p class="text-body-tertiary mb-4">You haven't requested any returns.</p>
            <a href="{{ route('account.orders') }}" class="btn btn-outline-primary">View orders</a>
        </div></div>
    @else
        {{-- Desktop Table --}}
        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-sm fs-9 mb-0">
                    <thead class="bg-body-highlight">
                        <tr>
                            <th class="ps-4 border-0 py-3">Reference</th>
                            <th class="border-0 py-3">Date</th>
                            <th class="border-0 py-3 text-center">Items</th>
                            <th class="border-0 py-3">Status</th>
                            <th class="text-end pe-4 border-0 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @foreach ($returns as $return)
                            <tr>
                                <td class="ps-4 py-3 align-middle fw-semibold text-body-emphasis">{{ $return->reference }}</td>
                                <td class="py-3 align-middle text-body-tertiary">{{ $return->created_at->format('M j, Y') }}</td>
                                <td class="py-3 align-middle text-center text-body-tertiary">{{ $return->items->sum('quantity') }}</td>
                                <td class="py-3 align-middle">
                                    <span class="badge badge-phoenix {{ $return->status->isSettled() ? 'badge-phoenix-success' : ($return->status->isOpen() ? 'badge-phoenix-warning' : 'badge-phoenix-secondary') }} rounded-pill">
                                        {{ $return->status->label() }}
                                    </span>
                                </td>
                                <td class="text-end pe-4 py-3 align-middle">
                                    <a href="{{ route('account.returns.show', $return) }}" class="btn btn-sm btn-phoenix-secondary">Track</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="d-md-none d-flex flex-column gap-3">
            @foreach ($returns as $return)
                <div class="card border-translucent shadow-none rounded-3">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-body-emphasis fs-8">{{ $return->reference }}</span>
                            <span class="badge badge-phoenix {{ $return->status->isSettled() ? 'badge-phoenix-success' : ($return->status->isOpen() ? 'badge-phoenix-warning' : 'badge-phoenix-secondary') }} rounded-pill fs-10">
                                {{ $return->status->label() }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 fs-9 text-body-secondary">
                            <span>{{ $return->created_at->format('M j, Y') }}</span>
                            <span>{{ $return->items->sum('quantity') }} item(s)</span>
                        </div>
                        <a href="{{ route('account.returns.show', $return) }}" class="btn btn-phoenix-secondary btn-sm w-100">Track Return</a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $returns->links() }}</div>
    @endif
@endsection
