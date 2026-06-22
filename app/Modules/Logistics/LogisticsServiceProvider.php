<?php

namespace App\Modules\Logistics;

use App\Modules\Logistics\Contracts\ShippingProvider;
use App\Modules\Logistics\Services\ManualShippingProvider;
use Illuminate\Support\ServiceProvider;

class LogisticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Default carrier is in-house manual dispatch. Swap this binding for a
        // GIG/Sendbox adapter when third-party fulfilment is enabled.
        $this->app->bind(ShippingProvider::class, ManualShippingProvider::class);
    }
}
