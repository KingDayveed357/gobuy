<?php

namespace App\Modules\Notification\Channels;

use App\Modules\Notification\Contracts\MessageChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends a message via the WhatsApp Cloud API. Uses the HTTP client — no extra
 * Composer dependency. Failures are logged; the queued job handles retries.
 */
class WhatsAppMessageChannel implements MessageChannel
{
    public function send(string $to, string $message): void
    {
        $config = config('gobuy.messaging.whatsapp');

        if (empty($config['phone_number_id']) || empty($config['token'])) {
            Log::warning('WhatsApp channel not configured; message skipped', ['to' => $to]);

            return;
        }

        Http::withToken($config['token'])
            ->acceptJson()
            ->post("{$config['base_url']}/{$config['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $this->normalise($to),
                'type' => 'text',
                'text' => ['body' => $message],
            ])
            ->throw();
    }

    /** WhatsApp expects an international number with no leading "+" or "0". */
    private function normalise(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            return '234'.substr($digits, 1); // Nigeria default
        }

        return $digits;
    }
}
