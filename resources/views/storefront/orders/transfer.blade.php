@extends('layouts.storefront')

@section('title', 'Complete your bank transfer — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <div class="mb-2"><span class="fas fa-building-columns fs-5 text-primary"></span></div>
                        <h2 class="mb-1">Complete your bank transfer</h2>
                        <p class="text-body-tertiary mb-0">Order {{ $order->order_number }}</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-subtle-success">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-subtle-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Transfer {{ money($order->total) }} to:</h5>
                            <dl class="row mb-0">
                                <dt class="col-5 col-sm-4 text-body-tertiary fw-normal">Bank</dt>
                                <dd class="col-7 col-sm-8 fw-semibold">{{ $bankAccount['bank'] }}</dd>
                                <dt class="col-5 col-sm-4 text-body-tertiary fw-normal">Account name</dt>
                                <dd class="col-7 col-sm-8 fw-semibold">{{ $bankAccount['account_name'] }}</dd>
                                <dt class="col-5 col-sm-4 text-body-tertiary fw-normal">Account number</dt>
                                <dd class="col-7 col-sm-8 fw-semibold">{{ $bankAccount['account_number'] }}</dd>
                                <dt class="col-5 col-sm-4 text-body-tertiary fw-normal">Use reference</dt>
                                <dd class="col-7 col-sm-8 fw-semibold">{{ $order->order_number }}</dd>
                            </dl>
                            <p class="fs-9 text-body-tertiary mt-3 mb-0">Your order ships once we confirm your payment.</p>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Upload your proof of payment</h5>
                            <form action="{{ route('orders.transfer.store', $order) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Amount paid (₦)</label>
                                        <input class="form-control" type="number" step="0.01" min="1" name="amount" value="{{ old('amount', $order->total->toNaira()) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Sender name <span class="text-body-tertiary">(optional)</span></label>
                                        <input class="form-control" type="text" name="sender_name" value="{{ old('sender_name') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Bank reference <span class="text-body-tertiary">(optional)</span></label>
                                        <input class="form-control" type="text" name="bank_reference" value="{{ old('bank_reference') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Receipt <span class="text-body-tertiary">(optional)</span></label>
                                        <input class="form-control" type="file" name="receipt" accept=".pdf,image/*">
                                    </div>
                                </div>
                                <button class="btn btn-primary mt-4" type="submit">Submit proof of payment</button>
                            </form>
                        </div>
                    </div>

                    @if ($order->transferProofs->isNotEmpty())
                        <div class="card">
                            <div class="card-body">
                                <h6 class="mb-3">Submitted proofs</h6>
                                @foreach ($order->transferProofs as $proof)
                                    <div class="d-flex flex-between-center border-bottom border-translucent py-2">
                                        <div>
                                            <span class="fw-semibold fs-9">{{ money($proof->amount) }}</span>
                                            <span class="fs-10 text-body-tertiary">· {{ $proof->created_at->format('M j, g:i A') }}</span>
                                        </div>
                                        <span class="badge badge-phoenix badge-phoenix-{{ $proof->status === 'approved' ? 'success' : ($proof->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($proof->status) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
