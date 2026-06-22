<?php

namespace App\Modules\Notification\Channels;

use App\Modules\Notification\Contracts\MessageChannel;
use Illuminate\Support\Facades\Log;

/**
 * Default channel: records the message to the log instead of sending. Safe for
 * local development and tests where no SMS/WhatsApp provider is configured.
 */
class LogMessageChannel implements MessageChannel
{
    public function send(string $to, string $message): void
    {
        Log::channel(config('logging.default'))->info('Customer message', [
            'to' => $to,
            'message' => $message,
        ]);
    }
}
