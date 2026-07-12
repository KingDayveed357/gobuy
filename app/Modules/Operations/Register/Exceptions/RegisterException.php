<?php

namespace App\Modules\Operations\Register\Exceptions;

use RuntimeException;

/** An invalid register action — e.g. opening a session while one is already open. */
class RegisterException extends RuntimeException {}
