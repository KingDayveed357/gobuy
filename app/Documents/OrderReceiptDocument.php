<?php

namespace App\Documents;

use App\Documents\Abstracts\BaseDocument;
use App\Modules\Order\Models\Order;

/**
 * OrderReceiptDocument
 *
 * Customer-facing order confirmation / receipt document. This is what the
 * customer receives after placing a paid order and what they would use as
 * proof of purchase. Equivalent to a Shopify order confirmation printout.
 *
 * Reuses the Order model loaded and authorized by OrderController::success()
 * — no duplication of auth logic or queries.
 */
class OrderReceiptDocument extends BaseDocument
{
    public function __construct(
        private readonly Order $order,
    ) {}

    public function getTitle(): string
    {
        return "Order Receipt #{$this->order->order_number} — " . config('app.name', 'GoBuy');
    }

    public function getDocumentType(): string
    {
        return 'Order Receipt';
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
        return 'documents.order-receipt';
    }

    public function getBackUrl(): ?string
    {
        return route('orders.success', $this->order);
    }
}
