<?php

namespace App\Modules\Order\Services;

use App\Modules\Order\Enums\OrderStatus;
use RuntimeException;

class InvalidOrderTransition extends RuntimeException
{
    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self("Cannot transition order from {$from->value} to {$to->value}.");
    }
}
