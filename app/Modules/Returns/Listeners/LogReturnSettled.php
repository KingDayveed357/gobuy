<?php

namespace App\Modules\Returns\Listeners;

use App\Modules\Returns\Events\ReturnSettled;
use Illuminate\Support\Facades\Log;

/**
 * Structured observability for settled returns — a correlation-friendly log
 * line that monitoring/alerting can key off.
 */
class LogReturnSettled
{
    public function handle(ReturnSettled $event): void
    {
        Log::info('Return settled', [
            'reference' => $event->return->reference,
            'order_id' => $event->return->order_id,
            'amount_kobo' => $event->amountKobo,
            'via' => $event->via,
        ]);
    }
}
