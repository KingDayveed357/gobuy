@extends('layouts.document')

@section('document')
{{--
    Order Receipt Document Template
    ─────────────────────────────────
    Customer-facing order receipt / confirmation document. Can be printed
    or saved as PDF directly from the order success page.

    Variables provided by OrderReceiptDocument::getData():
      $document — DocumentInterface
      $branding — array (from DocumentBrandingService)
      $order    — App\Modules\Order\Models\Order (loaded with items, payment, shipment)
--}}
@php
    use App\Modules\Order\Enums\PaymentMethod;
    use App\Modules\Order\Enums\PaymentStatus;

    $paymentTone = match ($order->payment_status) {
        PaymentStatus::Paid    => 'success',
        PaymentStatus::Failed  => 'danger',
        default                => 'warning',
    };

    $paymentRef = $order->payment?->reference;
    $paidAt     = $order->payment?->paid_at ?? ($order->isPaid() ? $order->updated_at : null);

    $shipment = $order->relationLoaded('shipment') ? $order->shipment : null;
    $isPickup = $shipment?->isPickup() ?? false;
@endphp

    {{-- ── Document header ──────────────────────────────────────────────── --}}
    <x-document.header :document="$document" :branding="$branding">
        @if ($order->isPaid())
            <x-document.status-pill tone="success">Paid</x-document.status-pill>
        @else
            <x-document.status-pill tone="warning">{{ $order->payment_status->label() }}</x-document.status-pill>
        @endif
    </x-document.header>

    {{-- ── Order metadata ────────────────────────────────────────────────── --}}
    <x-document.metadata-grid :items="[
        'Order Number'   => $order->order_number,
        'Order Date'     => $order->placed_at?->format('M j, Y g:i A'),
        'Order Status'   => $order->status->label(),
        'Payment Method' => $order->payment_method?->label() ?? '—',
        'Customer'       => $order->customer_name,
        'Contact'        => $order->customer_email . ($order->customer_phone ? ' · ' . $order->customer_phone : ''),
    ]" />

    {{-- ── Address blocks ───────────────────────────────────────────────── --}}
    <div class="doc-addresses">
        <x-document.address-block
            label="From"
            :name="$branding['store_name']"
            :lines="[$branding['address'], $branding['store_email'], $branding['store_phone']]"
        />
        <x-document.address-block
            :label="$isPickup ? 'Pickup Location' : 'Deliver To'"
            :name="$isPickup ? ($shipment?->pickupLocation?->name ?? $order->customer_name) : $order->customer_name"
            :lines="$isPickup
                ? [$shipment?->pickupLocation?->formatted(), $shipment?->pickupLocation?->opening_hours]
                : [$order->address_line, $order->city . ', ' . $order->state, $order->customer_phone]"
        />
    </div>

    {{-- ── Line items ───────────────────────────────────────────────────── --}}
    <x-document.section-title title="Order Items" />

    <div class="doc-table-wrapper">
        <table class="doc-table" role="table">
            <thead>
                <tr>
                    <th class="col-w-num">#</th>
                    <th class="col-w-auto">Item</th>
                    <th class="col-w-qty text-center">Qty</th>
                    <th class="col-w-price text-right">Unit Price</th>
                    <th class="col-w-total text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td class="text-center text-secondary text-xs">{{ $loop->iteration }}</td>
                        <td>
                            <span class="cell-item-name">{{ $item->name }}</span>
                            @if ($item->sku)
                                <span class="cell-item-sku">{{ $item->sku }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ money($item->unit_price) }}</td>
                        <td class="text-right font-semibold">{{ money($item->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Totals & Payment side-by-side ───────────────────────────────── --}}
    <div class="doc-cols" style="align-items: flex-start; margin-bottom: 20px;">
        {{-- Payment info --}}
        <x-document.payment-summary
            :method="$order->payment_method?->label() ?? '—'"
            :status="$order->payment_status->label()"
            :statusTone="$paymentTone"
            :reference="$paymentRef"
            :paidAt="$paidAt"
        >
            @if ($order->store_credit_applied?->kobo > 0)
                <div class="doc-payment__row">
                    <span class="doc-payment__row-label">Store Credit</span>
                    <span class="doc-payment__row-value">−{{ money($order->store_credit_applied) }}</span>
                </div>
            @endif
        </x-document.payment-summary>

        {{-- Totals --}}
        <div>
            <x-document.totals
                :subtotal="$order->subtotal"
                :discount="$order->discount_amount"
                :couponCode="$order->coupon_code"
                :delivery="$order->delivery_fee"
                :tax="$order->tax_amount"
                :storeCredit="$order->store_credit_applied"
                :total="$order->total"
                :refunded="$order->refunded_total"
            />
        </div>
    </div>

    {{-- ── Footer ───────────────────────────────────────────────────────── --}}
    <x-document.footer :document="$document" :branding="$branding">
        <p class="doc-footer__disclaimer" style="margin-top:4px;">
            Keep this receipt for your records. For queries contact us at
            {{ $branding['store_email'] ?? $branding['store_name'] }}.
        </p>
    </x-document.footer>

@endsection
