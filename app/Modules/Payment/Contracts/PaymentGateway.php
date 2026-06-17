<?php

namespace App\Modules\Payment\Contracts;

use App\Modules\Order\Models\Order;

interface PaymentGateway
{
    /**
     * Initialize a transaction and return the URL to redirect the customer to.
     *
     * @return array{authorization_url: string, reference: string}
     */
    public function initialize(Order $order, string $reference, string $callbackUrl): array;

    /**
     * Verify a transaction with the provider.
     *
     * @return array{success: bool, raw: array<string, mixed>}
     */
    public function verify(string $reference): array;

    /**
     * Refund a transaction (full or partial). Amount is in the major currency
     * unit (Naira); null refunds the full transaction.
     *
     * @return array{success: bool, raw: array<string, mixed>}
     */
    public function refund(string $reference, ?float $amount = null): array;
}
