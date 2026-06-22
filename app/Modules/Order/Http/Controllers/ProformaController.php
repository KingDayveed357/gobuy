<?php

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Services\CartService;
use App\Modules\Pricing\Services\TaxCalculator;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Renders a printable proforma invoice for the current wholesale cart. Wholesale
 * buyers typically need this document to raise a purchase order before paying.
 */
class ProformaController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly TaxCalculator $tax,
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

        return view('storefront.proforma', [
            ...$summary,
            'vat' => $vat,
            'user' => $user,
            'reference' => 'PRO-'.now()->format('ymd').'-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
        ]);
    }
}
