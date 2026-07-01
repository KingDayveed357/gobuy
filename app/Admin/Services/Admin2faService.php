<?php

namespace App\Admin\Services;

use App\Admin\Mail\AdminTwoFactorCodeMail;
use App\Admin\Models\Admin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Email one-time-code 2FA for admins. Codes live in the cache (hashed, 10-min
 * TTL) — no extra table — and are single-use.
 */
class Admin2faService
{
    private const TTL_SECONDS = 600;

    public function sendCode(Admin $admin): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->key($admin), Hash::make($code), self::TTL_SECONDS);

        Mail::to($admin->email)->send(new AdminTwoFactorCodeMail($admin, $code));
    }

    public function verify(Admin $admin, string $code): bool
    {
        $hash = Cache::get($this->key($admin));

        if (! $hash || ! Hash::check($code, $hash)) {
            return false;
        }

        Cache::forget($this->key($admin)); // single use

        return true;
    }

    private function key(Admin $admin): string
    {
        return "admin-2fa:{$admin->getKey()}";
    }
}
