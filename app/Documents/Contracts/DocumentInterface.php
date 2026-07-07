<?php

namespace App\Documents\Contracts;

/**
 * DocumentInterface
 *
 * Every printable document in the system must implement this contract.
 * It cleanly separates what a document IS (its identity and metadata)
 * from what it CONTAINS (its data) and how it is RENDERED (its view).
 *
 * Adding a new document type is as simple as implementing this interface,
 * creating one Blade template, and registering a route — the entire
 * rendering pipeline (branding, layout, print trigger) is inherited.
 */
interface DocumentInterface
{
    /**
     * The browser window / PDF title for this document.
     * Shown in the print dialog and used as the default PDF filename.
     */
    public function getTitle(): string;

    /**
     * The human-readable document type label printed in the document header.
     * Examples: "Proforma Invoice", "Order Receipt", "Daily Reconciliation Report"
     */
    public function getDocumentType(): string;

    /**
     * The unique document reference number, shown prominently on the document.
     * Examples: "PRO-260101-0042", "ORD-20260101-001", "REC-2026-01-01"
     */
    public function getReference(): string;

    /**
     * The data bag passed to the document's Blade template.
     * Must include all variables the template expects.
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * The Blade view name for this document's template.
     * Example: 'documents.order-receipt'
     */
    public function getView(): string;

    /**
     * Page orientation for this document.
     * Most documents are portrait; wide reports may prefer landscape.
     */
    public function getOrientation(): string;

    /**
     * The CSS @page size directive.
     * Examples: 'A4', 'Letter', 'A4 landscape'
     */
    public function getPageSize(): string;

    /**
     * Whether to render a watermark behind the document content.
     * Useful for draft, copy, or void documents.
     */
    public function showWatermark(): bool;

    /**
     * The watermark text to display when showWatermark() returns true.
     */
    public function getWatermarkText(): ?string;

    /**
     * Optional URL to navigate back to when the user is viewing the print preview.
     * If null, the back button is omitted.
     */
    public function getBackUrl(): ?string;
}
