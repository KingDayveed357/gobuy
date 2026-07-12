<?php

namespace App\Modules\Customer\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Exceptions\SocialAuthException;
use App\Modules\Customer\Services\OtpService;
use App\Modules\Customer\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * One provider-agnostic controller for every social provider. The `{provider}`
 * segment is validated against the enabled set in config/social.php, so adding
 * a provider never touches this class.
 */
class SocialAuthController extends Controller
{
    public function __construct(private readonly SocialAuthService $social) {}

    /** Send the shopper to the provider's consent screen (OAuth state set by Socialite). */
    public function redirect(string $provider, Request $request): RedirectResponse
    {
        $this->ensureEnabled($provider);

        // Optional post-auth return path (e.g. back to checkout). Only same-origin
        // relative paths are honoured — never an absolute/protocol-relative URL —
        // so this can't be abused as an open redirect.
        $return = $request->string('return')->toString();
        if (str_starts_with($return, '/') && ! str_starts_with($return, '//')) {
            redirect()->setIntendedUrl(url($return));
        }

        return Socialite::driver($provider)->redirect();
    }

    /** Handle the provider's callback: resolve identity, sign in, route onward. */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        $this->ensureEnabled($provider);

        try {
            $oauthUser = Socialite::driver($provider)->user(); // validates OAuth state
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->with('error', 'Social sign-in was cancelled or failed. Please try again.');
        }

        try {
            ['user' => $user, 'is_new' => $isNew] = $this->social->resolve($provider, $oauthUser);
        } catch (SocialAuthException $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }

        // Auth::login fires the Login event → the guest cart merges into this user.
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // Provider didn't vouch for the email (e.g. unverified Facebook) → OTP wall.
        if (! $user->hasVerifiedEmail()) {
            app(OtpService::class)->issue($user);

            return redirect()->route('verification.notice')
                ->with('status', 'Almost there — enter the code we emailed to verify your address.');
        }

        return redirect()->intended(route('account.dashboard'))
            ->with('status', $isNew ? 'Welcome to gobuy!' : 'Welcome back!');
    }

    private function ensureEnabled(string $provider): void
    {
        abort_unless((bool) config("social.providers.{$provider}.enabled", false), 404);
    }
}
