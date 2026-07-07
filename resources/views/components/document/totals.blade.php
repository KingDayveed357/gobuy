@props([
    'subtotal',
    'discount'      => null,
    'couponCode'    => null,
    'delivery'      => null,
    'tax'           => null,
    'storeCredit'   => null,
    'total',
    'refunded'      => null,
])

{{--
    Totals Panel Component
    ──────────────────────
    Right-aligned summary panel showing the order financial breakdown.
    Rows are shown conditionally — zero-value rows are omitted to keep
    the document clean. The grand total row uses inverted colours for
    visual hierarchy.

    Usage:
        <x-document.totals
            :subtotal="$order->subtotal"
            :discount="$order->discount_amount"
            :delivery="$order->delivery_fee"
            :tax="$order->tax_amount"
            :total="$order->total"
        />
--}}
<div class="doc-totals">
    <div class="doc-totals__inner">

        <div class="doc-totals__row">
            <span class="doc-totals__label">Subtotal</span>
            <span class="doc-totals__value">{{ money($subtotal) }}</span>
        </div>

        @if ($discount && $discount->kobo > 0)
            <div class="doc-totals__row doc-totals__row--discount">
                <span class="doc-totals__label">
                    Discount{{ $couponCode ? " ({$couponCode})" : '' }}
                </span>
                <span class="doc-totals__value">−{{ money($discount) }}</span>
            </div>
        @endif

        @if ($delivery && $delivery->kobo > 0)
            <div class="doc-totals__row">
                <span class="doc-totals__label">Delivery</span>
                <span class="doc-totals__value">{{ money($delivery) }}</span>
            </div>
        @endif

        @if ($tax && $tax->kobo > 0)
            <div class="doc-totals__row">
                <span class="doc-totals__label">VAT</span>
                <span class="doc-totals__value">{{ money($tax) }}</span>
            </div>
        @endif

        @if ($storeCredit && $storeCredit->kobo > 0)
            <div class="doc-totals__row">
                <span class="doc-totals__label">Store Credit Applied</span>
                <span class="doc-totals__value doc-totals__value--discount">−{{ money($storeCredit) }}</span>
            </div>
        @endif

        <div class="doc-totals__row doc-totals__row--grand">
            <span class="doc-totals__label">Total</span>
            <span class="doc-totals__value">{{ money($total) }}</span>
        </div>

        @if ($refunded && $refunded->kobo > 0)
            <div class="doc-totals__row" style="background:#fef2f2;">
                <span class="doc-totals__label" style="color:var(--doc-red);">Refunded</span>
                <span class="doc-totals__value" style="color:var(--doc-red);">−{{ money($refunded) }}</span>
            </div>
        @endif

    </div>
</div>
