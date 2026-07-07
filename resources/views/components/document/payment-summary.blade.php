@props([
    'method',             // string — e.g. "Paystack", "Bank Transfer", "Pay on Delivery"
    'status',             // string — e.g. "Paid", "Pending", "Failed"
    'statusTone'=> 'neutral', // 'success'|'danger'|'warning'|'neutral'
    'reference' => null,  // string|null — payment reference / transaction ID
    'paidAt'    => null,  // Carbon|null — when payment was confirmed
])

{{--
    Payment Summary Component
    ─────────────────────────
    A compact information block showing payment method, current status,
    reference number, and confirmation timestamp. Placed beneath the
    totals panel or beside the address blocks depending on document type.
--}}
<div class="doc-payment">
    <div class="doc-payment__title">Payment</div>

    <div class="doc-payment__row">
        <span class="doc-payment__row-label">Method</span>
        <span class="doc-payment__row-value">{{ $method }}</span>
    </div>

    <div class="doc-payment__row">
        <span class="doc-payment__row-label">Status</span>
        <span class="doc-payment__row-value">
            <x-document.status-pill :tone="$statusTone">{{ $status }}</x-document.status-pill>
        </span>
    </div>

    @if ($reference)
        <div class="doc-payment__row">
            <span class="doc-payment__row-label">Reference</span>
            <span class="doc-payment__row-value" style="font-family: monospace; font-size: 7.5pt;">
                {{ $reference }}
            </span>
        </div>
    @endif

    @if ($paidAt)
        <div class="doc-payment__row">
            <span class="doc-payment__row-label">Confirmed</span>
            <span class="doc-payment__row-value">{{ $paidAt->format('M j, Y g:i A') }}</span>
        </div>
    @endif

    {{ $slot }}
</div>
