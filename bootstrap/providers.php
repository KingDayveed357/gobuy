<?php

use App\Admin\AdminServiceProvider;
use App\Modules\Logistics\LogisticsServiceProvider;
use App\Modules\Notification\NotificationServiceProvider;
use App\Modules\Payment\PaymentServiceProvider;
use App\Modules\Returns\ReturnsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ModuleServiceProvider;

return [
    AppServiceProvider::class,
    ModuleServiceProvider::class,
    PaymentServiceProvider::class,
    LogisticsServiceProvider::class,
    NotificationServiceProvider::class,
    ReturnsServiceProvider::class,
    AdminServiceProvider::class,
];
