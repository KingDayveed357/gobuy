<?php

namespace App\Modules\Customer\Services;

use App\Models\User;
use App\Modules\Customer\Mail\OtpCodeMail;
use App\Modules\Customer\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Issues and verifies one-time passcodes. Codes are 6 digits, hashed at rest,
 * valid for a short window, single-use, and superseded on re-issue.
 */
class OtpService
{
    private const TTL_MINUTES = 10;

    /** Minimum seconds a user must wait before requesting another code. */
    public const RESEND_COOLDOWN_SECONDS = 30;

    /**
     * Generate a fresh code, persist its hash, email it to the user, and
     * invalidate any earlier unconsumed codes for the same purpose.
     */
    public function issue(User $user, string $purpose = OtpCode::PURPOSE_EMAIL_VERIFICATION): void
    {
        $user->otpCodes()->where('purpose', $purpose)->whereNull('consumed_at')->update([
            'consumed_at' => now(),
        ]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->otpCodes()->create([
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        Mail::to($user->email)->queue(new OtpCodeMail($user, $code, self::TTL_MINUTES));
    }

    /**
     * Verify a submitted code. On success the code is consumed and the user's
     * email is marked verified (for the email-verification purpose).
     */
    public function verify(User $user, string $code, string $purpose = OtpCode::PURPOSE_EMAIL_VERIFICATION): bool
    {
        $record = $user->otpCodes()
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($code, $record->code_hash)) {
            return false;
        }

        $record->update(['consumed_at' => now()]);

        if ($purpose === OtpCode::PURPOSE_EMAIL_VERIFICATION && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return true;
    }

    /**
     * Whether the user is allowed to request a new code right now.
     * Returns false if a code was issued within the cooldown window.
     */
    public function canResend(User $user, string $purpose = OtpCode::PURPOSE_EMAIL_VERIFICATION): bool
    {
        return $this->secondsUntilResend($user, $purpose) === 0;
    }

    /**
     * Seconds remaining before the user may resend. Returns 0 when they can
     * resend immediately (no prior code, or cooldown has fully elapsed).
     */
    public function secondsUntilResend(User $user, string $purpose = OtpCode::PURPOSE_EMAIL_VERIFICATION): int
    {
        $latest = $user->otpCodes()
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if (! $latest) {
            return 0;
        }

        $elapsed = (int) $latest->created_at->diffInSeconds(now());
        $remaining = self::RESEND_COOLDOWN_SECONDS - $elapsed;

        return max(0, $remaining);
    }
}
