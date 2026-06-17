<?php

namespace App\Modules\Payment;

use App\Modules\Payment\Contracts\PaymentGateway;
use App\Modules\Payment\Services\PaystackGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function (): PaystackGateway {
            return new PaystackGateway(
                secretKey: (string) config('services.paystack.secret_key'),
                baseUrl: (string) config('services.paystack.base_url'),
            );
        });
    }
}
