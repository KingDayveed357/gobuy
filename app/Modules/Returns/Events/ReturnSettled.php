<?php

namespace App\Modules\Returns\Events;

use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A return has been financially settled (refunded to original method or store
 * credit). Carries the kobo amount and the channel it went out through.
 */
class ReturnSettled
{
    use Dispatchable;

    public function __construct(
        public readonly ReturnRequest $return,
        public readonly int $amountKobo,
        public readonly ?string $via,
    ) {}
}
