<?php

namespace App\Modules\Returns;

use App\Modules\Returns\Events\ReturnRequested;
use App\Modules\Returns\Events\ReturnSettled;
use App\Modules\Returns\Events\ReturnStatusChanged;
use App\Modules\Returns\Listeners\LogReturnSettled;
use App\Modules\Returns\Listeners\ScoreReturnRisk;
use App\Modules\Returns\Listeners\SendReturnStatusNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Returns module's domain events to their listeners. Keeping this
 * mapping explicit (rather than relying on auto-discovery) documents the
 * event-driven seams of the returns lifecycle in one place.
 */
class ReturnsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(ReturnRequested::class, ScoreReturnRisk::class);
        Event::listen(ReturnStatusChanged::class, SendReturnStatusNotification::class);
        Event::listen(ReturnSettled::class, LogReturnSettled::class);
    }
}
