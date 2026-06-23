@extends('layouts.storefront')

@section('title', 'Store credit — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('account.dashboard') }}">Account</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Store credit</li>
                </ol>
            </nav>

            <div class="card mb-4 bg-primary-subtle border-0">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <p class="fs-9 text-body-tertiary mb-1"><span class="fas fa-wallet me-1"></span>Available store credit</p>
                        <h2 class="mb-0">{{ money($balance) }}</h2>
                    </div>
                    <a href="{{ route('products.index') }}" class="btn btn-primary btn-sm">Shop now</a>
                </div>
            </div>

            <h5 class="mb-3">History</h5>
            @if ($entries->isEmpty())
                <div class="card"><div class="card-body text-center py-5 text-body-tertiary">No store-credit activity yet.</div></div>
            @else
                <div class="card"><div class="card-body p-0">
                    <table class="table table-sm mb-0 fs-9">
                        <thead><tr><th>Date</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr>
                                    <td>{{ $entry->created_at->format('M j, Y') }}</td>
                                    <td>{{ $entry->reason ?? ucfirst(str_replace('_', ' ', $entry->type)) }}</td>
                                    <td class="text-end fw-semibold {{ $entry->amount->kobo >= 0 ? 'text-success' : 'text-body-emphasis' }}">
                                        {{ $entry->amount->kobo >= 0 ? '+' : '−' }}{{ money(\App\Support\Money::fromKobo(abs($entry->amount->kobo))) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div></div>
                <div class="mt-4">{{ $entries->links() }}</div>
            @endif
        </div>
    </section>
@endsection
