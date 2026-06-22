<?php

namespace App\Modules\Notification;

use App\Modules\Notification\Channels\LogMessageChannel;
use App\Modules\Notification\Channels\SmsMessageChannel;
use App\Modules\Notification\Channels\WhatsAppMessageChannel;
use App\Modules\Notification\Contracts\MessageChannel;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MessageChannel::class, function (): MessageChannel {
            return match (config('gobuy.messaging.driver')) {
                'whatsapp' => new WhatsAppMessageChannel,
                'sms' => new SmsMessageChannel,
                default => new LogMessageChannel,
            };
        });
    }
}
