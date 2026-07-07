@extends('layouts.document')

@section('document')
{{--
    Daily Reconciliation Report Document
    ─────────────────────────────────────
    Finance-facing daily reconciliation report — printable version.
    Mirrors the exact data from PaymentController::reconciliation() with
    a clean, printable layout suitable for filing with bank statements.

    Variables provided by ReconciliationDocument::getData():
      $document        — DocumentInterface
      $branding        — array
      $date            — Carbon
      $byMethod        — Collection (label, count, total per PaymentMethod)
      $ordersTotal     — Money
      $collected       — Money
      $outstanding     — Money
      $fellThrough     — Money
      $fellThroughCount — int
      $paystackSettled — Money
      $bankConfirmed   — Money
--}}

    {{-- ── Document header ──────────────────────────────────────────────── --}}
    <x-document.header :document="$document" :branding="$branding">
        <span style="font-size:8pt; color: var(--doc-ink-3);">
            For the date: <strong>{{ $date->format('l, F j, Y') }}</strong>
        </span>
    </x-document.header>

    {{-- ── KPI summary cards ────────────────────────────────────────────── --}}
    <x-document.section-title title="Summary" />

    <div class="doc-stats-grid" style="margin-bottom: 24px;">

        <div class="doc-stat">
            <div class="doc-stat__label">Total Orders Placed</div>
            <div class="doc-stat__value">{{ money($ordersTotal) }}</div>
            <div class="doc-stat__hint">Gross order value for {{ $date->format('M j') }}</div>
        </div>

        <div class="doc-stat" style="border-color: var(--doc-green); background: var(--doc-green-light);">
            <div class="doc-stat__label" style="color: var(--doc-green);">Collected</div>
            <div class="doc-stat__value" style="color: var(--doc-green);">{{ money($collected) }}</div>
            <div class="doc-stat__hint">Confirmed paid orders</div>
        </div>

        <div class="doc-stat" style="border-color: var(--doc-amber); background: var(--doc-amber-light);">
            <div class="doc-stat__label" style="color: var(--doc-amber);">Outstanding</div>
            <div class="doc-stat__value" style="color: var(--doc-amber);">{{ money($outstanding) }}</div>
            <div class="doc-stat__hint">Expected but not yet confirmed</div>
        </div>

        <div class="doc-stat" style="border-color: var(--doc-red); background: var(--doc-red-light);">
            <div class="doc-stat__label" style="color: var(--doc-red);">Cancelled / Failed</div>
            <div class="doc-stat__value" style="color: var(--doc-red);">{{ money($fellThrough) }}</div>
            <div class="doc-stat__hint">{{ $fellThroughCount }} order(s) — not collectable</div>
        </div>

    </div>

    {{-- ── Two-column: by method + settlement check ─────────────────────── --}}
    <div class="doc-cols" style="align-items: flex-start; margin-bottom: 24px;">

        {{-- By payment method --}}
        <div>
            <x-document.section-title title="By Payment Method" />
            <table class="doc-table">
                <thead>
                    <tr>
                        <th class="col-w-auto">Method</th>
                        <th class="col-w-qty text-center">Orders</th>
                        <th class="col-w-total text-right">Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($byMethod as $row)
                        @if ($row['count'] > 0)
                            <tr>
                                <td class="font-semibold">{{ $row['label'] }}</td>
                                <td class="text-center">{{ $row['count'] }}</td>
                                <td class="text-right">{{ money($row['total']) }}</td>
                            </tr>
                        @endif
                    @endforeach

                    @if ($byMethod->every(fn ($r) => $r['count'] === 0))
                        <tr>
                            <td colspan="3" class="text-center text-secondary" style="padding:16px 10px;">
                                No orders placed on {{ $date->format('M j, Y') }}.
                            </td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td class="font-bold text-right" colspan="2">Total</td>
                        <td class="font-bold text-right">{{ money($ordersTotal) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Settlement check --}}
        <div>
            <x-document.section-title title="Settlement Check" />
            <div class="doc-payment">
                <div class="doc-payment__title">Compare against bank &amp; gateway</div>

                <div class="doc-payment__row">
                    <span class="doc-payment__row-label">Paystack settlements</span>
                    <span class="doc-payment__row-value">{{ money($paystackSettled) }}</span>
                </div>
                <div class="doc-payment__row">
                    <span class="doc-payment__row-label">Bank transfers confirmed</span>
                    <span class="doc-payment__row-value">{{ money($bankConfirmed) }}</span>
                </div>
                <div class="doc-payment__row" style="border-top: 2px solid var(--doc-border-md); margin-top: 4px; padding-top: 8px;">
                    <span class="doc-payment__row-label font-bold">Total Collected</span>
                    <span class="doc-payment__row-value font-bold" style="font-size: 10pt;">{{ money($collected) }}</span>
                </div>
            </div>

            <div class="doc-notice doc-notice--info" style="margin-top: 12px; margin-bottom: 0;">
                Compare these figures against your bank statement and Paystack dashboard
                for <strong>{{ $date->format('M j, Y') }}</strong>.
            </div>
        </div>

    </div>

    {{-- ── Variance analysis ────────────────────────────────────────────── --}}
    @php
        $varianceKobo = $collected->kobo - $paystackSettled->kobo - $bankConfirmed->kobo;
        $hasVariance  = abs($varianceKobo) > 0;
    @endphp
    @if ($hasVariance)
        <div class="doc-notice {{ $varianceKobo > 0 ? 'doc-notice--warn' : 'doc-notice--danger' }}">
            <strong>Variance detected:</strong>
            Collected amount ({{ money($collected) }}) differs from settlement total
            ({{ money(\App\Support\Money::fromKobo($paystackSettled->kobo + $bankConfirmed->kobo)) }})
            by <strong>{{ money(\App\Support\Money::fromKobo(abs($varianceKobo))) }}</strong>.
            Please investigate before closing the day.
        </div>
    @else
        <div class="doc-notice doc-notice--info">
            <strong>Balanced:</strong> Collected amount matches settlement records.
        </div>
    @endif

    {{-- ── Footer ───────────────────────────────────────────────────────── --}}
    <x-document.footer :document="$document" :branding="$branding">
        <p class="doc-footer__disclaimer" style="margin-top:4px;">
            This report is auto-generated from system records as at {{ now()->format('M j, Y g:i A') }}.
            Figures represent data recorded in the GoBuy system and may not reflect same-day bank processing delays.
        </p>
    </x-document.footer>

@endsection
