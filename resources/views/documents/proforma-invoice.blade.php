@extends('layouts.document')

@section('document')
{{--
    Proforma Invoice Document Template
    ────────────────────────────────────
    Wholesale proforma invoice. Replaces the old storefront proforma which
    printed inside the full storefront layout including nav and footer.

    Variables provided by ProformaInvoiceDocument::getData():
      $document  — DocumentInterface
      $branding  — array (from DocumentBrandingService)
      $user      — App\Models\User (with wholesaleProfile relation)
      $lines     — array of cart line arrays
      $subtotal  — Money
      $vat       — Money
      $total     — Money
      $reference — string (e.g. PRO-260101-0042)
--}}

    {{-- ── Document header ──────────────────────────────────────────────── --}}
    <x-document.header :document="$document" :branding="$branding" />

    {{-- ── Metadata grid ────────────────────────────────────────────────── --}}
    <x-document.metadata-grid :items="[
        'Issue Date'    => now()->format('M j, Y'),
        'Valid Until'   => now()->addDays(7)->format('M j, Y'),
        'Reference'     => $reference,
        'Currency'      => $branding['currency'],
    ]" />

    {{-- ── Address blocks ───────────────────────────────────────────────── --}}
    <div class="doc-addresses">
        <x-document.address-block
            label="Sold By"
            :name="$branding['store_name']"
            :lines="[$branding['address'], $branding['store_email'], $branding['store_phone']]"
        />
        <x-document.address-block
            label="Bill To"
            :name="$user->wholesaleProfile?->business_name ?? $user->name"
            :lines="[
                $user->email,
                $user->wholesaleProfile?->business_address,
                $user->wholesaleProfile?->business_phone ?? $user->phone ?? null,
            ]"
        />
    </div>

    {{-- ── Line items ───────────────────────────────────────────────────── --}}
    <x-document.section-title title="Items" />

    {{-- Proforma lines are arrays, not models. The items-table handles both. --}}
    <div class="doc-table-wrapper">
        <table class="doc-table" role="table">
            <thead>
                <tr>
                    <th class="col-w-num">#</th>
                    <th class="col-w-sku">SKU</th>
                    <th class="col-w-auto">Product</th>
                    <th class="col-w-qty text-center">Qty</th>
                    <th class="col-w-price text-right">Unit Price</th>
                    <th class="col-w-total text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr>
                        <td class="text-center text-secondary text-xs">{{ $loop->iteration }}</td>
                        <td class="text-xs text-secondary">{{ $line['item']->variant->sku ?? '—' }}</td>
                        <td>
                            <span class="cell-item-name">{{ $line['item']->variant->product->name }}</span>
                            @if ($line['item']->variant->label() !== 'Default')
                                <span class="cell-item-variant">{{ $line['item']->variant->label() }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $line['item']->quantity }}</td>
                        <td class="text-right">{{ money($line['price']->unitPrice) }}</td>
                        <td class="text-right font-semibold">{{ money($line['lineTotal']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ── Totals ────────────────────────────────────────────────────────── --}}
    <x-document.totals
        :subtotal="$subtotal"
        :tax="$vat"
        :total="$total"
    />

    {{-- ── Notice ────────────────────────────────────────────────────────── --}}
    <div class="doc-notice doc-notice--info">
        <strong>Note:</strong> This proforma invoice is valid for 7 days from the issue date.
        Delivery costs are not included and will be quoted at checkout based on destination and order weight.
        Prices are inclusive of VAT at the applicable rate.
    </div>

    {{-- ── Footer ───────────────────────────────────────────────────────── --}}
    <x-document.footer :document="$document" :branding="$branding" />

@endsection
