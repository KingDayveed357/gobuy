<?php

namespace App\Modules\Payment\Jobs;

use App\Modules\Payment\Models\WebhookPayload;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\RefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly WebhookPayload $webhookPayload) {}

    /**
     * Execute the job.
     */
    public function handle(PaymentService $payments, RefundService $refunds): void
    {
        if ($this->webhookPayload->status !== 'pending') {
            return;
        }

        $this->webhookPayload->update(['status' => 'processing']);

        try {
            $payload = $this->webhookPayload->payload;

            match ($this->webhookPayload->event_type) {
                'charge.success' => $this->onChargeSuccess($payments, $payload),
                // Paystack identifies the refund object by data.id (the same id the
                // /refund response returned and we stored as provider_reference).
                'refund.processed' => $refunds->markConfirmed((string) data_get($payload, 'data.id'), $payload),
                'refund.failed' => $refunds->markFailed((string) data_get($payload, 'data.id'), $payload),
                default => null, // unhandled events are recorded but ignored
            };

            $this->webhookPayload->update(['status' => 'processed']);
        } catch (\Throwable $e) {
            $this->webhookPayload->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            Log::error('Webhook processing failed', [
                'webhook_payload_id' => $this->webhookPayload->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function onChargeSuccess(PaymentService $payments, array $payload): void
    {
        $reference = data_get($payload, 'data.reference');

        if ($reference) {
            $payments->verifyAndComplete($reference);
        }
    }
}
