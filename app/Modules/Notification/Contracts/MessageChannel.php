<?php

namespace App\Modules\Notification\Contracts;

/**
 * A transport for short customer messages (SMS or WhatsApp). Implementations
 * deliver a plain-text message to a phone number. The active implementation is
 * chosen by config('gobuy.messaging.driver').
 */
interface MessageChannel
{
    public function send(string $to, string $message): void;
}
