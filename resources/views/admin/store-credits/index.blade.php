@extends('admin.layouts.app')

@section('title', 'Store credit — gobuy admin')
@section('page-title', 'Store credit')

@section('content')
    <x-admin.page-header title="Store credit" subtitle="Customer wallet balances and the ledger behind them." />

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card"><div class="card-body">
                <h5 class="mb-3">Issue credit</h5>
              
                <form action="{{ route('admin.store-credits.issue') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">Customer email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="amount">Amount (₦)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="reason">Reason</label>
                        <input type="text" class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" value="{{ old('reason') }}" placeholder="e.g. Goodwill / service recovery" maxlength="160" required>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><span class="fas fa-plus me-2"></span>Issue credit</button>
                </form>
            </div></div>
        </div>

        <div class="col-12 col-xl-8">
            <x-admin.table
                :cols="[['label' => 'Customer'], ['label' => 'Email'], ['label' => 'Balance', 'align' => 'end'], ['label' => '', 'align' => 'end']]"
                :empty="$wallets->isEmpty()"
                empty-icon="fa-wallet"
                empty-text="No wallets yet."
            >
                <x-slot:toolbar>
                    <form method="GET" class="admin-toolbar mb-0 w-100 d-flex gap-2">
                        <input class="form-control form-control-sm" style="max-width:300px" type="search" name="q" value="{{ request('q') }}" placeholder="Search customer">
                        <button class="btn btn-sm btn-phoenix-secondary" type="submit">Search</button>
                    </form>
                </x-slot:toolbar>

                @foreach ($wallets as $wallet)
                    <tr>
                        <td class="fw-semibold text-body-emphasis">{{ $wallet->user?->name }}</td>
                        <td>{{ $wallet->user?->email }}</td>
                        <td class="text-end fw-semibold">{{ money($wallet->balance) }}</td>
                        <td class="text-end"><a href="{{ route('admin.store-credits.show', $wallet->user) }}" class="btn btn-sm btn-phoenix-secondary">Ledger</a></td>
                    </tr>
                @endforeach
            </x-admin.table>
            <div class="mt-4">{{ $wallets->links() }}</div>
        </div>
    </div>
@endsection
