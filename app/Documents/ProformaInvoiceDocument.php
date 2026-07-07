<?php

namespace App\Documents;

use App\Documents\Abstracts\BaseDocument;
use App\Models\User;
use App\Support\Money;

/**
 * ProformaInvoiceDocument
 *
 * Represents a wholesale proforma invoice generated from the current cart.
 * Wholesale buyers use this document to raise a purchase order internally
 * before completing payment. It is valid for 7 days and excludes delivery.
 *
 * Data is pre-computed by ProformaController (cart summary + VAT calculation)
 * and passed directly — no additional queries are made here.
 */
class ProformaInvoiceDocument extends BaseDocument
{
    /**
     * @param  array<int, array{item: mixed, price: mixed, lineTotal: Money}>  $lines
     */
    public function __construct(
        private readonly User   $user,
        private readonly array  $lines,
        private readonly Money  $subtotal,
        private readonly Money  $vat,
        private readonly string $reference,
    ) {}

    public function getTitle(): string
    {
        return "Proforma Invoice {$this->reference} — " . config('app.name', 'GoBuy');
    }

    public function getDocumentType(): string
    {
        return 'Proforma Invoice';
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getData(): array
    {
        return [
            'user'      => $this->user,
            'lines'     => $this->lines,
            'subtotal'  => $this->subtotal,
            'vat'       => $this->vat,
            'reference' => $this->reference,
            'total'     => $this->subtotal->plus($this->vat),
        ];
    }

    public function getView(): string
    {
        return 'documents.proforma-invoice';
    }

    public function getBackUrl(): ?string
    {
        return route('cart.index');
    }
}
