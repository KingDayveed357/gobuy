<?php

namespace App\Modules\Customer\Exceptions;

use RuntimeException;

/**
 * A social sign-in could not be completed safely (no email from the provider,
 * an unverified email colliding with an existing account, etc.). The message is
 * user-facing and shown as a friendly error on the login screen.
 */
class SocialAuthException extends RuntimeException
{
    public static function noEmail(string $providerLabel): self
    {
        return new self("We couldn't get an email address from {$providerLabel}. Please try another sign-in method or grant email access.");
    }

    public static function emailTaken(string $providerLabel): self
    {
        return new self("An account with this email already exists. Please sign in with your password, then link {$providerLabel} from your account settings.");
    }
}
