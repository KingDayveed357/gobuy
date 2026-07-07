@extends('layouts.document')

@section('document')
{{--
    Admin Order — Packing Slip / Dispatch Document
    ─────────────────────────────────────────────────
    Operations-facing print view used by warehouse staff for packing and
    dispatch. Includes full SKU detail, variant information, quantity
    boxes for picking, and payment reference for finance reconciliation.

    Variables provided by AdminOrderDocument::getData():
      $document — DocumentInterface
      $branding — array (from DocumentBrandingService)
      $order    — App\Modules\Order\Models\Order
                  (loaded with items.variant.product, payment, shipment)
--}}
@php
    use App\Modules\Order\Enums\PaymentStatus;
    use App\Modules\Order\Enums\OrderStatus;

    $paymentTone = match ($order->payment_status) {
        PaymentStatus::Paid   => 'success',
        PaymentStatus::Failed => 'danger',
        default               => 'warning',
    };

    $orderTone = match ($order->status) {
        OrderStatus::Completed, OrderStatus::Delivered => 'success',
        OrderStatus::Cancelled                          => 'danger',
        OrderStatus::Processing, OrderStatus::Shipped   => 'info',
        default                                         => 'neutral',
    };

    $totalItems = $order->items->sum('quantity');
    $shipment   = $order->relationLoaded('shipment') ? $order->shipment : null;
    $isPickup   = $shipment?->isPickup() ?? false;
@endphp

    {{-- ── Document header ──────────────────────────────────────────────── --}}
    <x-document.header :document="$document" :branding="$branding">
        <x-document.status-pill :tone="$orderTone">{{ $order->status->label() }}</x-document.status-pill>
    </x-document.header>

    {{-- ── Order metadata ────────────────────────────────────────────────── --}}
    <x-document.metadata-grid :items="[
        'Order #'        => $order->order_number,
        'Placed'         => $order->placed_at?->format('M j, Y g:i A'),
        'Order Status'   => $order->status->label(),
        'Payment Status' => $order->payment_status->label(),
        'Payment Method' => $order->payment_method?->label() ?? '—',
        'Total Items'    => $totalItems . ' ' . Str::plural('item', $totalItems),
    ]" />

    {{-- ── Address blocks ───────────────────────────────────────────────── --}}
    <div class="doc-addresses">
        <x-document.address-block
            label="Customer"
            :name="$order->customer_name"
            :lines="[$order->customer_email, $order->customer_phone]"
        />
        <x-document.address-block
            :label="$isPickup ? 'Pickup Location' : 'Deliver To'"
            :name="$isPickup ? ($shipment?->pickupLocation?->name ?? 'Pickup') : $order->customer_name"
            :lines="$isPickup
                ? [$shipment?->pickupLocation?->formatted(), $shipment?->pickupLocation?->opening_hours]
                : [$order->address_line, $order->city . ', ' . $order->state]"
        />
    </div>

    {{-- ── Packing checklist ────────────────────────────────────────────── --}}
    <x-document.section-title title="Items — Packing Checklist" />

    <div class="doc-table-wrapper">
        <table class="doc-table" role="table">
            <thead>
                <tr>
                    <th class="col-w-num">#</th>
                    <th class="col-w-sku">SKU</th>
                    <th class="col-w-auto">Product / Variant</th>
                    <th class="col-w-qty text-center">Ordered</th>
                    <th class="col-w-price text-right">Unit Price</th>
                    <th class="col-w-total text-right">Line Total</th>
                    <th style="width:60px; text-align:center;" class="print-only">Packed ✓</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td class="text-center text-secondary text-xs">{{ $loop->iteration }}</td>
                        <td class="text-xs text-secondary" style="font-family: monospace;">
                            {{ $item->sku ?? $item->variant?->sku ?? '—' }}
                        </td>
                        <td>
                            <span class="cell-item-name">{{ $item->name }}</span>
                            @if ($item->variant?->attributeLabel())
                                <span class="cell-item-variant">{{ $item->variant->attributeLabel() }}</span>
                            @endif
                        </td>
                        <td class="text-center font-bold">{{ $item->quantity }}</td>
                        <td class="text-right">{{ money($item->unit_price) }}</td>
                        <td class="text-right font-semibold">{{ money($item->line_total) }}</td>
                        {{-- Empty box for warehouse staff to tick --}}
                        <td class="text-center print-only" style="border: 1.5px solid #94a3b8; width: 20px; height: 20px;"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Financial summary + payment side-by-side ────────────────────── --}}
    <div class="doc-cols" style="align-items: flex-start; margin-bottom: 20px;">
        {{-- Payment details for finance --}}
        <x-document.payment-summary
            :method="$order->payment_method?->label() ?? '—'"
            :status="$order->payment_status->label()"
            :statusTone="$paymentTone"
            :reference="$order->payment?->reference"
            :paidAt="$order->payment?->paid_at"
        >
            @if ($order->store_credit_applied?->kobo > 0)
                <div class="doc-payment__row">
                    <span class="doc-payment__row-label">Store Credit Applied</span>
                    <span class="doc-payment__row-value">{{ money($order->store_credit_applied) }}</span>
                </div>
            @endif
            @if ($order->coupon_code)
                <div class="doc-payment__row">
                    <span class="doc-payment__row-label">Coupon</span>
                    <span class="doc-payment__row-value font-bold" style="font-family:monospace; font-size:7.5pt;">{{ $order->coupon_code }}</span>
                </div>
            @endif
        </x-document.payment-summary>

        {{-- Order totals --}}
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

    {{-- ── Delivery notes ───────────────────────────────────────────────── --}}
    @if ($shipment)
        <div class="doc-payment" style="margin-bottom: 20px;">
            <div class="doc-payment__title">Delivery / Logistics</div>
            <div class="doc-payment__row">
                <span class="doc-payment__row-label">Method</span>
                <span class="doc-payment__row-value">{{ $shipment->methodLabel() }}</span>
            </div>
            @if ($shipment->tracking_number)
                <div class="doc-payment__row">
                    <span class="doc-payment__row-label">Tracking #</span>
                    <span class="doc-payment__row-value" style="font-family:monospace;">{{ $shipment->tracking_number }}</span>
                </div>
            @endif
            @if ($shipment->carrier)
                <div class="doc-payment__row">
                    <span class="doc-payment__row-label">Carrier</span>
                    <span class="doc-payment__row-value">{{ $shipment->carrier }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- ── Signature line (packing slip) ──────────────────────────────── --}}
    <div class="doc-cols print-only" style="margin-top: 28px; margin-bottom: 8px; gap: 40px;">
        <div>
            <div style="border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 7pt; color: #94a3b8;">
                Packed by (name &amp; signature)
            </div>
        </div>
        <div>
            <div style="border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 7pt; color: #94a3b8;">
                Dispatched by (name &amp; signature)
            </div>
        </div>
        <div>
            <div style="border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 7pt; color: #94a3b8;">
                Date
            </div>
        </div>
    </div>

    {{-- ── Footer ───────────────────────────────────────────────────────── --}}
    <x-document.footer :document="$document" :branding="$branding">
        <p class="doc-footer__disclaimer" style="margin-top:4px;">
            Internal document — for operational use only. Not a valid tax invoice.
        </p>
    </x-document.footer>

@endsection
