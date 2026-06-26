<?php

namespace App\Modules\Payment\Jobs;

use App\Modules\Payment\Models\WebhookPayload;
use App\Modules\Payment\Services\PaymentService;
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
    public function __construct(public readonly WebhookPayload $webhookPayload)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentService $payments): void
    {
        if ($this->webhookPayload->status !== 'pending') {
            return;
        }

        $this->webhookPayload->update(['status' => 'processing']);

        try {
            // Placeholder: currently delegating charge.success to PaymentService.
            // Full processor routing logic will be implemented in Milestone 3.
            if ($this->webhookPayload->event_type === 'charge.success') {
                $reference = data_get($this->webhookPayload->payload, 'data.reference');
                if ($reference) {
                    $payments->verifyAndComplete($reference);
                }
            }
            
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
}
