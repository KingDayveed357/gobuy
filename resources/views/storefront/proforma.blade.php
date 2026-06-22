@extends('layouts.storefront')

@section('title', 'Proforma invoice — gobuy')

@section('content')
    <section class="pt-5 pb-9">
        <div class="container-small">
            <div class="d-flex flex-between-center mb-4 d-print-none">
                <a href="{{ route('cart.index') }}" class="btn btn-phoenix-secondary btn-sm"><span class="fas fa-chevron-left me-1"></span>Back to cart</a>
                <button onclick="window.print()" class="btn btn-primary btn-sm"><span class="fas fa-print me-2"></span>Print / Save as PDF</button>
            </div>

            <div class="card">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-between-center flex-wrap mb-4">
                        <div>
                            <h2 class="mb-0">GoBuy</h2>
                            <p class="fs-9 text-body-tertiary mb-0">Port Harcourt, Nigeria</p>
                        </div>
                        <div class="text-end">
                            <h4 class="mb-1">Proforma Invoice</h4>
                            <p class="fs-9 text-body-tertiary mb-0">{{ $reference }}</p>
                            <p class="fs-9 text-body-tertiary mb-0">{{ now()->format('M j, Y') }}</p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <p class="fs-10 text-body-tertiary mb-1">Billed to</p>
                        <p class="fw-semibold mb-0">{{ $user->wholesaleProfile?->business_name ?? $user->name }}</p>
                        <p class="fs-9 text-body-secondary mb-0">{{ $user->email }}@if ($user->wholesaleProfile?->business_address) · {{ $user->wholesaleProfile->business_address }}@endif</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Unit</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                @foreach ($lines as $line)
                                    <tr>
                                        <td>{{ $line['item']->variant->product->name }}<br><span class="fs-10 text-body-tertiary">{{ $line['item']->variant->sku }}</span></td>
                                        <td class="text-center">{{ $line['item']->quantity }}</td>
                                        <td class="text-end">{{ money($line['price']->unitPrice) }}</td>
                                        <td class="text-end fw-semibold">{{ money($line['lineTotal']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr><td colspan="3" class="text-end text-body-tertiary">Subtotal</td><td class="text-end">{{ money($subtotal) }}</td></tr>
                                <tr><td colspan="3" class="text-end text-body-tertiary">VAT</td><td class="text-end">{{ money($vat) }}</td></tr>
                                <tr><td colspan="3" class="text-end fw-bold">Total (excl. delivery)</td><td class="text-end fw-bold">{{ money($subtotal->plus($vat)) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>

                    <p class="fs-10 text-body-tertiary mb-0">This proforma is valid for 7 days and excludes delivery, which is quoted at checkout based on destination and weight.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
