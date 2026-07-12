<?php

namespace App\Modules\Operations\Purchasing\Exceptions;

use RuntimeException;

/** An invalid purchasing action — e.g. receiving against a draft or cancelled PO. */
class PurchasingException extends RuntimeException {}
