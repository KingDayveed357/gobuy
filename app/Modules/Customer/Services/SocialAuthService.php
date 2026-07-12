<?php

namespace App\Modules\Customer\Services;

use App\Models\User;
use App\Modules\Customer\Exceptions\SocialAuthException;
use App\Modules\Customer\Models\SocialAccount;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Resolves an OAuth identity to a local customer, enforcing the account-linking
 * rules that keep one human = one customer:
 *
 *   1. A known (provider, provider_id) → that customer signs in.
 *   2. A provider-VERIFIED email matching an existing customer → link the
 *      identity to them (no duplicate; every order/address/wishlist/credit is
 *      preserved because it is the same user row).
 *   3. Otherwise → create a new customer, marking the email verified only when
 *      the provider vouched for it (so Google skips our OTP wall; an unverified
 *      Facebook email still falls back to OTP).
 *
 * Security: linking only ever happens on a provider-verified email, so an
 * attacker cannot claim a victim's account by presenting an unverified address.
 */
class SocialAuthService
{
    /**
     * @return array{user: User, is_new: bool}
     */
    public function resolve(string $provider, SocialiteUser $oauthUser): array
    {
        // 1. Returning social user — identity already linked.
        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $oauthUser->getId())
            ->first();

        if ($account) {
            $this->storeTokens($account, $oauthUser);

            return ['user' => $account->user, 'is_new' => false];
        }

        $email = $oauthUser->getEmail();
        $emailVerified = $this->providerEmailIsVerified($provider, $oauthUser);

        if (! $email) {
            throw SocialAuthException::noEmail($this->label($provider));
        }

        $existingUser = User::where('email', $email)->first();

        // 2. Link to an existing customer — only when the provider verified the email.
        if ($existingUser) {
            if (! $emailVerified) {
                throw SocialAuthException::emailTaken($this->label($provider));
            }

            return DB::transaction(function () use ($existingUser, $provider, $oauthUser): array {
                $this->link($existingUser, $provider, $oauthUser);

                // The provider proved ownership — clear any pending OTP wall.
                if (! $existingUser->hasVerifiedEmail()) {
                    $existingUser->markEmailAsVerified();
                    event(new Verified($existingUser));
                }

                return ['user' => $existingUser, 'is_new' => false];
            });
        }

        // 3. Brand-new customer.
        return DB::transaction(function () use ($provider, $oauthUser, $email, $emailVerified): array {
            $user = new User([
                'name' => $oauthUser->getName() ?: ($oauthUser->getNickname() ?: 'Customer'),
                'email' => $email,
                'role' => User::ROLE_CUSTOMER,
                'customer_type' => User::TYPE_RETAIL,
                'wholesale_status' => User::WHOLESALE_NONE,
            ]);
            $user->password = null; // social-only until they set one
            if ($emailVerified) {
                $user->email_verified_at = now();
            }
            $user->save();

            $this->link($user, $provider, $oauthUser);

            if ($emailVerified) {
                event(new Verified($user));
            }

            return ['user' => $user, 'is_new' => true];
        });
    }

    /**
     * Attach (or refresh) a provider identity on a user. Idempotent per provider.
     */
    public function link(User $user, string $provider, SocialiteUser $oauthUser): SocialAccount
    {
        return $user->socialAccounts()->updateOrCreate(
            ['provider' => $provider, 'provider_id' => $oauthUser->getId()],
            [
                'provider_email' => $oauthUser->getEmail(),
                'avatar' => $oauthUser->getAvatar(),
                'token' => $oauthUser->token ?? null,
                'refresh_token' => $oauthUser->refreshToken ?? null,
            ],
        );
    }

    /**
     * Whether we can trust that the provider verified the returned email.
     * Google guarantees it for every account; other providers must return a
     * truthy per-user verified claim, otherwise we treat it as unverified.
     */
    private function providerEmailIsVerified(string $provider, SocialiteUser $oauthUser): bool
    {
        if (config("social.providers.{$provider}.email_always_verified", false)) {
            return true;
        }

        $raw = (array) ($oauthUser->user ?? []);

        return (bool) ($raw['email_verified'] ?? $raw['verified_email'] ?? $raw['verified'] ?? false);
    }

    private function storeTokens(SocialAccount $account, SocialiteUser $oauthUser): void
    {
        $account->update([
            'token' => $oauthUser->token ?? $account->token,
            'refresh_token' => $oauthUser->refreshToken ?? $account->refresh_token,
            'avatar' => $oauthUser->getAvatar() ?: $account->avatar,
        ]);
    }

    private function label(string $provider): string
    {
        return config("social.providers.{$provider}.label", ucfirst($provider));
    }
}
