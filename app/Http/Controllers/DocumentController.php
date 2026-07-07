<?php

namespace App\Http\Controllers;

use App\Documents\OrderReceiptDocument;
use App\Modules\Order\Models\Order;
use App\Services\DocumentRenderService;
use Illuminate\Contracts\View\View;

/**
 * DocumentController (storefront)
 *
 * Handles all customer-facing document print/preview routes. Auth and
 * access control follow the same pattern as the originating controllers
 * to avoid any duplication of authorization logic. The Order model is
 * loaded with the same relationships as the screen views to prevent N+1.
 */
class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentRenderService $renderer,
    ) {}

    /**
     * Customer order receipt — accessible by the authenticated owner or
     * a guest who placed the order in this browser session.
     */
    public function orderReceipt(Order $order): View
    {
        $this->authorizeOrderAccess($order);

        $order->load(['items', 'payment', 'shipment.pickupLocation']);

        return $this->renderer->render(new OrderReceiptDocument($order));
    }

    /**
     * Mirrors the authorization logic from OrderController::authorizeAccess()
     * to keep the access rules in sync without coupling to that controller.
     */
    private function authorizeOrderAccess(Order $order): void
    {
        $owns      = auth()->check() && auth()->id() === $order->user_id;
        $placedHere = in_array($order->id, session('viewable_orders', []), true);

        abort_unless($owns || $placedHere, 403);
    }
}
