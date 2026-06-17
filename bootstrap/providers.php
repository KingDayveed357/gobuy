<?php

use App\Admin\AdminServiceProvider;
use App\Modules\Payment\PaymentServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ModuleServiceProvider;

return [
    AppServiceProvider::class,
    ModuleServiceProvider::class,
    PaymentServiceProvider::class,
    AdminServiceProvider::class,
];
