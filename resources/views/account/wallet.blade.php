@extends('layouts.account')

@section('title', 'Store Credit — Quintessential Mart')

@php
    $pageTitle = 'Store Credit';
@endphp

@section('account_content')
    <div class="mb-4">
        <h3 class="mb-0 text-body-emphasis">Store Credit</h3>
        <p class="text-body-tertiary">View your wallet balance and recent transactions.</p>
    </div>

    {{-- Digital Wallet Card --}}
    <div class="card bg-primary text-white border-0 shadow-sm mb-5 position-relative overflow-hidden" style="border-radius: 1rem;">
        <!-- Background decoration -->
        <div class="position-absolute end-0 top-0 h-100 w-50" style="background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1)); clip-path: polygon(25% 0%, 100% 0%, 100% 100%, 0% 100%); pointer-events: none;"></div>
        <div class="card-body p-4 p-md-5 position-relative z-1 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
            <div>
                <p class="fs-9 text-white-50 fw-semibold mb-2 text-uppercase tracking-wide"><span class="fas fa-wallet me-2"></span>Available Balance</p>
                <h1 class="display-5 text-white fw-bolder mb-0 lh-1">{{ money($balance) }}</h1>
            </div>
            <div class="text-md-end">
                <a href="{{ route('products.index') }}" class="btn btn-light fw-bold shadow-sm px-4">Shop Now</a>
            </div>
        </div>
    </div>

    <h5 class="mb-4 text-body-emphasis">Transaction History</h5>
    @if ($entries->isEmpty())
        <div class="card border-0 shadow-sm"><div class="card-body text-center py-6">
            <span class="fas fa-receipt fs-1 text-body-tertiary mb-3"></span>
            <h5 class="text-body-emphasis mb-2">No transactions yet</h5>
            <p class="text-body-tertiary mb-0">Your store credit activity will appear here.</p>
        </div></div>
    @else
        {{-- Desktop Table --}}
        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-sm fs-9 mb-0">
                    <thead class="bg-body-highlight">
                        <tr>
                            <th class="ps-4 border-0 py-3">Date</th>
                            <th class="border-0 py-3">Description</th>
                            <th class="text-end pe-4 border-0 py-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @foreach ($entries as $entry)
                            <tr>
                                <td class="ps-4 py-3 align-middle text-body-tertiary">{{ $entry->created_at->format('M j, Y • h:i A') }}</td>
                                <td class="py-3 align-middle fw-semibold text-body-emphasis">{{ $entry->reason ?? ucfirst(str_replace('_', ' ', $entry->type)) }}</td>
                                <td class="text-end pe-4 py-3 align-middle fw-bold {{ $entry->amount->kobo >= 0 ? 'text-success' : 'text-body-emphasis' }}">
                                    {{ $entry->amount->kobo >= 0 ? '+' : '−' }}{{ money(\App\Support\Money::fromKobo(abs($entry->amount->kobo))) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards (Timeline) --}}
        <div class="d-md-none d-flex flex-column gap-3">
            @foreach ($entries as $entry)
                <div class="card border-translucent shadow-none rounded-3">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="fw-semibold text-body-emphasis mb-1">{{ $entry->reason ?? ucfirst(str_replace('_', ' ', $entry->type)) }}</p>
                            <p class="fs-10 text-body-tertiary mb-0">{{ $entry->created_at->format('M j, Y • h:i A') }}</p>
                        </div>
                        <div class="fw-bold fs-8 {{ $entry->amount->kobo >= 0 ? 'text-success' : 'text-body-emphasis' }}">
                            {{ $entry->amount->kobo >= 0 ? '+' : '−' }}{{ money(\App\Support\Money::fromKobo(abs($entry->amount->kobo))) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-4">{{ $entries->links() }}</div>
    @endif
@endsection
