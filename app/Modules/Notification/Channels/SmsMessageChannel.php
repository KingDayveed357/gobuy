<?php

namespace App\Modules\Notification\Channels;

use App\Modules\Notification\Contracts\MessageChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends a message via a generic HTTP SMS gateway (e.g. Termii / Africa's
 * Talking style). Configure the endpoint and key in config('gobuy.messaging.sms').
 */
class SmsMessageChannel implements MessageChannel
{
    public function send(string $to, string $message): void
    {
        $config = config('gobuy.messaging.sms');

        if (empty($config['base_url']) || empty($config['api_key'])) {
            Log::warning('SMS channel not configured; message skipped', ['to' => $to]);

            return;
        }

        Http::asJson()
            ->post($config['base_url'], [
                'api_key' => $config['api_key'],
                'to' => $to,
                'from' => $config['sender_id'],
                'sms' => $message,
            ])
            ->throw();
    }
}
