<?php

namespace App\Modules\Order\Http\Controllers;

use App\Documents\ProformaInvoiceDocument;
use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Pricing\Services\TaxCalculator;
use App\Services\DocumentRenderService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Renders a printable proforma invoice for the current wholesale cart. Wholesale
 * buyers typically need this document to raise a purchase order before paying.
 *
 * The proforma URL (/proforma) now renders directly via the document system —
 * a clean standalone page with a floating print bar rather than the full
 * storefront layout. The page IS the document.
 */
class ProformaController extends Controller
{
    public function __construct(
        private readonly CartService           $cart,
        private readonly TaxCalculator         $tax,
        private readonly DocumentRenderService $renderer,
    ) {}

    public function show(): View|RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user->isWholesale(), 403);

        $summary = $this->cart->summary();

        if (empty($summary['lines'])) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        $vat = Money::zero();
        foreach ($summary['lines'] as $line) {
            $vat = $vat->plus($this->tax->lineVat($line['item']->variant->product, $line['lineTotal']));
        }

        $reference = 'PRO-' . now()->format('ymd') . '-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);

        return $this->renderer->render(new ProformaInvoiceDocument(
            user:      $user,
            lines:     $summary['lines'],
            subtotal:  $summary['subtotal'],
            vat:       $vat,
            reference: $reference,
        ));
    }
}
