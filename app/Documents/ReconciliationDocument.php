<?php

namespace App\Documents;

use App\Documents\Abstracts\BaseDocument;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * ReconciliationDocument
 *
 * Finance-facing daily payment reconciliation report. Shows all payment
 * totals for a given date broken down by method, with settlement figures
 * for comparison against the bank statement and Paystack dashboard.
 *
 * The raw data arrays are passed in pre-computed by PaymentController,
 * keeping this class a pure document description with no query logic.
 */
class ReconciliationDocument extends BaseDocument
{
    public function __construct(
        private readonly Carbon     $date,
        private readonly Collection $byMethod,
        private readonly Money      $ordersTotal,
        private readonly Money      $collected,
        private readonly Money      $outstanding,
        private readonly Money      $fellThrough,
        private readonly int        $fellThroughCount,
        private readonly Money      $paystackSettled,
        private readonly Money      $bankConfirmed,
    ) {}

    public function getTitle(): string
    {
        return 'Daily Reconciliation — ' . $this->date->format('M j, Y') . ' — ' . config('app.name', 'GoBuy');
    }

    public function getDocumentType(): string
    {
        return 'Daily Reconciliation Report';
    }

    public function getReference(): string
    {
        return 'REC-' . $this->date->format('Y-m-d');
    }

    public function getData(): array
    {
        return [
            'date'             => $this->date,
            'byMethod'         => $this->byMethod,
            'ordersTotal'      => $this->ordersTotal,
            'collected'        => $this->collected,
            'outstanding'      => $this->outstanding,
            'fellThrough'      => $this->fellThrough,
            'fellThroughCount' => $this->fellThroughCount,
            'paystackSettled'  => $this->paystackSettled,
            'bankConfirmed'    => $this->bankConfirmed,
        ];
    }

    public function getView(): string
    {
        return 'documents.reconciliation';
    }

    public function getBackUrl(): ?string
    {
        return route('admin.reconciliation', ['date' => $this->date->toDateString()]);
    }
}
