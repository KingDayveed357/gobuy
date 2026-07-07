<?php

namespace App\Documents;

use App\Documents\Abstracts\BaseDocument;
use App\Modules\Order\Models\Order;

/**
 * AdminOrderDocument
 *
 * Operations-facing order print document — used by warehouse staff and
 * admins as a packing slip, dispatch note, or internal order record.
 * Includes additional operational details not shown on the customer receipt:
 * SKUs, variant labels, admin order links, and payment method detail.
 *
 * Reuses the fully-loaded Order model from Admin\OrderController::show()
 * without any additional queries.
 */
class AdminOrderDocument extends BaseDocument
{
    public function __construct(
        private readonly Order $order,
    ) {}

    public function getTitle(): string
    {
        return "Order #{$this->order->order_number} — " . config('app.name', 'GoBuy') . ' Admin';
    }

    public function getDocumentType(): string
    {
        return 'Order — Packing Slip';
    }

    public function getReference(): string
    {
        return $this->order->order_number;
    }

    public function getData(): array
    {
        return [
            'order' => $this->order,
        ];
    }

    public function getView(): string
    {
        return 'documents.admin-order';
    }

    public function getBackUrl(): ?string
    {
        return route('admin.orders.show', $this->order);
    }
}
