<?php

namespace App\Modules\Payment\Services;

use App\Modules\Order\Models\Order;
use App\Modules\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $baseUrl,
    ) {}

    public function initialize(Order $order, string $reference, string $callbackUrl): array
    {
        $response = $this->client()
            ->post('/transaction/initialize', [
                'email' => $order->customer_email,
                'amount' => (int) round($order->total * 100), // Paystack expects kobo
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => ['order_number' => $order->order_number],
            ])
            ->throw()
            ->json();

        if (! ($response['status'] ?? false)) {
            throw new RuntimeException('Paystack initialization failed: '.($response['message'] ?? 'unknown error'));
        }

        return [
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $response['data']['reference'] ?? $reference,
        ];
    }

    public function verify(string $reference): array
    {
        $response = $this->client()
            ->get("/transaction/verify/{$reference}")
            ->throw()
            ->json();

        $success = ($response['status'] ?? false)
            && ($response['data']['status'] ?? null) === 'success';

        return ['success' => $success, 'raw' => $response];
    }

    public function refund(string $reference, ?float $amount = null): array
    {
        $payload = ['transaction' => $reference];

        if ($amount !== null) {
            $payload['amount'] = (int) round($amount * 100); // kobo
        }

        $response = $this->client()
            ->post('/refund', $payload)
            ->throw()
            ->json();

        return ['success' => (bool) ($response['status'] ?? false), 'raw' => $response];
    }

    private function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->timeout(20)
            ->connectTimeout(10)
            ->retry(2, 200);
    }
}
