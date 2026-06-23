<?php

namespace App\Modules\Returns\Events;

use App\Modules\Returns\Enums\ReturnStatus;
use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A return moved from one lifecycle state to another. The integration seam for
 * notifications, metrics, and (future) multi-vendor routing.
 */
class ReturnStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly ReturnRequest $return,
        public readonly ?ReturnStatus $from,
        public readonly ReturnStatus $to,
    ) {}
}
