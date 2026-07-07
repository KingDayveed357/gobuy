<?php

namespace App\Services;

use App\Documents\Contracts\DocumentInterface;
use Illuminate\Contracts\View\View;

/**
 * DocumentRenderService
 *
 * The single point of entry for rendering any document in the system.
 * Accepts a DocumentInterface, merges the document's own data with the
 * shared branding context, and returns a ready-to-serve View response.
 *
 * To add a new output channel (e.g. PDF generation via Browsershot, email
 * via Mailable, or thermal receipt via ESC/POS), add a new method here and
 * inject this service wherever that channel is needed. The document definition
 * itself never changes — only the rendering target does.
 */
class DocumentRenderService
{
    public function __construct(
        private readonly DocumentBrandingService $branding,
    ) {}

    /**
     * Render a document to the browser (screen + print preview).
     *
     * The standalone `layouts.document` layout handles all print/screen
     * presentation — the document's Blade template contains only content.
     */
    public function render(DocumentInterface $document): View
    {
        return view($document->getView(), array_merge(
            $document->getData(),
            [
                'document' => $document,
                'branding' => $this->branding->getBranding(),
            ],
        ));
    }

    /**
     * Future: render to raw HTML string (useful for PDF generation,
     * email inlining, or headless testing).
     */
    public function toHtml(DocumentInterface $document): string
    {
        return $this->render($document)->render();
    }
}
