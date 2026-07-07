<?php

namespace App\Admin\Controllers;

use App\Documents\AdminOrderDocument;
use App\Documents\ReconciliationDocument;
use App\Http\Controllers\Controller;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\BankTransferProof;
use App\Modules\Payment\Models\Payment;
use App\Services\DocumentRenderService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Admin\DocumentController
 *
 * Handles all admin-facing document print/preview routes.
 * Delegates data preparation to the same query patterns used by the screen
 * controllers — no business logic is duplicated, only the rendering target
 * is changed (document layout instead of admin app layout).
 */
class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentRenderService $renderer,
    ) {}

    /**
     * Admin order packing slip / dispatch document.
     * Loads the same relationships as Admin\OrderController::show().
     */
    public function order(Order $order): View
    {
        $order->load(['items.variant.product.media', 'statusHistories', 'payment', 'shipment.pickupLocation']);

        return $this->renderer->render(new AdminOrderDocument($order));
    }

    /**
     * Daily reconciliation report — printable version.
     * The data preparation is extracted from PaymentController::reconciliation()
     * so both the screen view and the print view share identical computation.
     */
    public function reconciliation(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date')->toString())
            : Carbon::today();

        $orders = Order::whereDate('placed_at', $date)->get();

        $byMethod = collect(\App\Modules\Order\Enums\PaymentMethod::cases())->map(
            fn (\App\Modules\Order\Enums\PaymentMethod $m) => [
                'label' => $m->label(),
                'count' => $orders->where('payment_method', $m)->count(),
                'total' => Money::fromKobo(
                    (int) $orders->where('payment_method', $m)->sum(fn ($o) => $o->total->kobo)
                ),
            ]
        );

        $paidOrders = $orders->where('payment_status', PaymentStatus::Paid);

        $fellThrough = $orders->filter(fn (Order $o) =>
            $o->payment_status !== PaymentStatus::Paid
            && ($o->status === OrderStatus::Cancelled || $o->payment_status === PaymentStatus::Failed)
        );

        $outstandingOrders = $orders->filter(fn (Order $o) =>
            $o->payment_status !== PaymentStatus::Paid
            && $o->status !== OrderStatus::Cancelled
            && $o->status !== OrderStatus::Refunded
            && $o->payment_status !== PaymentStatus::Failed
        );

        $document = new ReconciliationDocument(
            date:             $date,
            byMethod:         $byMethod,
            ordersTotal:      Money::fromKobo((int) $orders->sum(fn ($o) => $o->total->kobo)),
            collected:        Money::fromKobo((int) $paidOrders->sum(fn ($o) => $o->total->kobo)),
            outstanding:      Money::fromKobo((int) $outstandingOrders->sum(fn ($o) => $o->total->kobo)),
            fellThrough:      Money::fromKobo((int) $fellThrough->sum(fn ($o) => $o->total->kobo)),
            fellThroughCount: $fellThrough->count(),
            paystackSettled:  Money::fromKobo(
                (int) Payment::where('status', 'success')
                    ->whereDate('paid_at', $date)->get()
                    ->sum(fn ($p) => $p->amount->kobo)
            ),
            bankConfirmed:    Money::fromKobo(
                (int) BankTransferProof::where('status', 'approved')
                    ->whereDate('reviewed_at', $date)->get()
                    ->sum(fn ($p) => $p->amount->kobo)
            ),
        );

        return $this->renderer->render($document);
    }
}
