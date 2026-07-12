<?php

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

/**
 * A stock movement would drive on-hand below zero without being allowed to. Kept
 * a RuntimeException so existing catchers of the old decrement guard still work.
 */
class InsufficientStock extends RuntimeException {}
