@extends('admin.layouts.app')

@section('title', 'Bank transfers — Quintessential Mart admin')
@section('page-title', 'Bank transfers')

@section('content')
    <x-admin.page-header title="Bank transfer reconciliation" subtitle="{{ $proofs->total() }} awaiting review">
        <x-slot:actions>
            <a href="{{ route('admin.reconciliation') }}" class="btn btn-phoenix-secondary"><span class="fas fa-scale-balanced me-2"></span>Daily report</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($proofs->isEmpty())
        <x-admin.card>
            <div class="text-center py-6">
                <div class="mb-2"><span class="fas fa-building-columns fs-5 text-body-tertiary"></span></div>
                <h6 class="mb-0">No transfers awaiting reconciliation.</h6>
            </div>
        </x-admin.card>
    @else
        <div class="row g-3">
            @foreach ($proofs as $proof)
                <div class="col-12 col-xl-6">
                    <x-admin.card>
                        <div class="d-flex flex-between-center mb-2">
                            <div>
                                <a href="{{ route('admin.orders.show', $proof->order) }}" class="fw-semibold text-body-emphasis text-decoration-none">{{ $proof->order->order_number }}</a>
                                <p class="fs-9 text-body-tertiary mb-0">{{ $proof->order->customer_name }} · {{ $proof->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="text-end">
                                <p class="fw-bold mb-0">{{ money($proof->amount) }}</p>
                                <p class="fs-10 text-body-tertiary mb-0">order {{ money($proof->order->total) }}</p>
                            </div>
                        </div>
                        <dl class="row fs-9 mb-2">
                            <dt class="col-4 text-body-tertiary fw-normal">Sender</dt>
                            <dd class="col-8 mb-1">{{ $proof->sender_name ?: '—' }}</dd>
                            <dt class="col-4 text-body-tertiary fw-normal">Reference</dt>
                            <dd class="col-8 mb-0">{{ $proof->bank_reference ?: '—' }}</dd>
                        </dl>
                        @if ($proof->receiptUrl())
                            <a href="{{ $proof->receiptUrl() }}" target="_blank" class="btn btn-sm btn-phoenix-secondary mb-3"><span class="fas fa-file me-1"></span>View receipt</a>
                        @endif
                        <div class="d-flex gap-2 border-top border-translucent pt-3">
                            <button type="button" class="btn btn-sm btn-phoenix-success" data-bs-toggle="modal" data-bs-target="#actionModal" data-action="{{ route('admin.transfers.approve', $proof) }}" data-method="POST" data-title="Confirm Payment" data-message="Are you sure you want to confirm this payment and mark the order as paid?" data-confirm-text="Yes, confirm payment" data-variant="success">Confirm payment</button>
                            <form action="{{ route('admin.transfers.reject', $proof) }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <input class="form-control form-control-sm" name="note" placeholder="Reason (optional)" style="width: 160px;">
                                <button class="btn btn-sm btn-phoenix-danger">Reject</button>
                            </form>
                        </div>
                    </x-admin.card>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $proofs->links() }}</div>
    @endif
@endsection
