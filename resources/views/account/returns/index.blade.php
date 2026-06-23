@extends('layouts.storefront')

@section('title', 'My returns — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Returns</li>
                </ol>
            </nav>
            <h2 class="mb-4">My returns</h2>

            @if ($returns->isEmpty())
                <div class="card"><div class="card-body text-center py-5">
                    <p class="text-body-tertiary mb-3">You haven't requested any returns yet.</p>
                    <a href="{{ route('account.orders') }}" class="btn btn-primary btn-sm">View orders</a>
                </div></div>
            @else
                <div class="card"><div class="card-body p-0">
                    <table class="table table-sm mb-0 fs-9">
                        <thead><tr>
                            <th>Reference</th><th>Date</th><th>Items</th><th>Status</th><th></th>
                        </tr></thead>
                        <tbody>
                            @foreach ($returns as $return)
                                <tr>
                                    <td class="fw-semibold text-body-emphasis">{{ $return->reference }}</td>
                                    <td>{{ $return->created_at->format('M j, Y') }}</td>
                                    <td>{{ $return->items->sum('quantity') }}</td>
                                    <td><span class="badge badge-phoenix {{ $return->status->isSettled() ? 'badge-phoenix-success' : ($return->status->isOpen() ? 'badge-phoenix-warning' : 'badge-phoenix-secondary') }}">{{ $return->status->label() }}</span></td>
                                    <td class="text-end"><a href="{{ route('account.returns.show', $return) }}" class="btn btn-sm btn-phoenix-secondary">Track</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div></div>
                <div class="mt-4">{{ $returns->links() }}</div>
            @endif
        </div>
    </section>
@endsection
