<?php

namespace App\Modules\Returns\Events;

use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A customer has submitted a new return request (before risk scoring).
 */
class ReturnRequested
{
    use Dispatchable;

    public function __construct(public readonly ReturnRequest $return) {}
}
