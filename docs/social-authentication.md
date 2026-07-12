# Social Authentication (Google & Facebook)

Production-grade social login for the GoBuy customer storefront, built on
[Laravel Socialite](https://laravel.com/docs/socialite). Designed as a reusable
commerce-platform feature: adding a provider is a config entry, not new code.

---

## 1. Architecture Overview

### Provider abstraction
`config/social.php` is the single source of truth. Routes, buttons and the
callback controller are all generated over the **enabled** providers:

```php
'providers' => [
    'google'   => ['enabled' => env('GOOGLE_AUTH_ENABLED', true),   'label' => 'Google',   'email_always_verified' => true],
    'facebook' => ['enabled' => env('FACEBOOK_AUTH_ENABLED', true), 'label' => 'Facebook', 'email_always_verified' => false],
],
```

Adding **Apple / Microsoft / X / GitHub / LinkedIn / TikTok** later:
1. Add a block to `config/social.php`.
2. Add its credentials to `config/services.php` + `.env`.
3. (If Socialite core doesn't ship it) `composer require socialiteproviders/<name>`.

No changes to the controller, service, routes, or UI.

### OAuth flow
```
Storefront button ── GET /auth/{provider}/redirect ──► Provider consent screen
                                                              │
Provider ── GET /auth/{provider}/callback ◄───────────────────┘
   │  Socialite validates the OAuth `state` (CSRF) and exchanges the code
   ▼
SocialAuthController::callback
   ▼
SocialAuthService::resolve(provider, oauthUser)   ← identity resolution
   ▼
Auth::login(user)  →  fires Login  →  guest cart merges
   ▼
verified?  ── no ──►  issue OTP  →  /email/verify
           ── yes ─►  redirect()->intended(account.dashboard)
```

### Identity resolution & account linking (`SocialAuthService`)
Resolution runs in this exact order:

1. **Known identity** — a `(provider, provider_id)` row exists → sign that
   customer in. (Returning social user.)
2. **Verified-email match** — the provider's email is verified **and** matches an
   existing customer → **link** the identity to them. No duplicate account; every
   order, address, wishlist item, cart, store credit and coupon is preserved
   because it is the **same `users` row** (nothing is copied or moved).
3. **New customer** — create a user with a `null` password (social-only) and set
   `email_verified_at` only when the provider vouched for the email.

`social_accounts` has a **unique `(provider, provider_id)`** index, so one
provider identity maps to exactly one customer.

### Callback / verification flow
See the "Email Verification Strategy" section below — the OTP flow for
email/password registration is untouched; social sign-ins skip it only when the
provider guarantees a verified email.

### Session flow
`Auth::login()` (used by the callback) fires the framework `Login` event, which
the existing `MergeGuestCart` listener already handles — so a shopper's guest
cart survives a social sign-in with no extra code. The session id is regenerated
on login to prevent fixation.

---

## 2. Environment Variables

| Variable | Purpose |
|---|---|
| `GOOGLE_AUTH_ENABLED` | Master switch. When `false`, no Google button renders and its routes 404. |
| `GOOGLE_CLIENT_ID` | OAuth client id from Google Cloud Console. |
| `GOOGLE_CLIENT_SECRET` | OAuth client secret from Google Cloud Console. |
| `GOOGLE_REDIRECT_URI` | Must exactly match an "Authorized redirect URI" in Google, e.g. `https://yourdomain.com/auth/google/callback`. |
| `FACEBOOK_AUTH_ENABLED` | Master switch for Facebook. |
| `FACEBOOK_CLIENT_ID` | App ID from the Meta developer portal. |
| `FACEBOOK_CLIENT_SECRET` | App Secret from the Meta developer portal. |
| `FACEBOOK_REDIRECT_URI` | Must match a "Valid OAuth Redirect URI" in Meta, e.g. `https://yourdomain.com/auth/facebook/callback`. |

A copy of these lives in `.env.example`.

---

## 3. Google Cloud Setup Guide

1. Open the [Google Cloud Console](https://console.cloud.google.com/) and create
   (or select) a project — top bar → project dropdown → **New Project**.
2. **OAuth consent screen** → [console](https://console.cloud.google.com/apis/credentials/consent):
   - User type **External**, fill app name, support email, developer email.
   - Scopes: add `.../auth/userinfo.email` and `.../auth/userinfo.profile`.
   - While in **Testing**, add your own Google account under **Test users**.
3. **Credentials** → [console](https://console.cloud.google.com/apis/credentials)
   → **Create Credentials → OAuth client ID**:
   - Application type **Web application**.
   - **Authorized redirect URIs** → add `https://yourdomain.com/auth/google/callback`
     (and `http://localhost/auth/google/callback` for local dev — see §5).
   - No "Authorized JavaScript origins" are needed (this is a server-side flow).
4. Copy the **Client ID** and **Client secret** into `.env`
   (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`), set `GOOGLE_REDIRECT_URI`, and
   `GOOGLE_AUTH_ENABLED=true`.
5. **Test locally** (§5), then publish the consent screen (**Publish App**) to
   let any Google user sign in in production.

Docs: [Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2).

---

## 4. Facebook (Meta) Developer Setup Guide

1. Create a developer account at [developers.facebook.com](https://developers.facebook.com/).
2. [My Apps](https://developers.facebook.com/apps/) → **Create App** → use case
   **Authenticate and request data from users with Facebook Login** → app type
   **Consumer**.
3. In the app dashboard, add the **Facebook Login** product.
4. **Facebook Login → Settings**:
   - **Valid OAuth Redirect URIs**: `https://yourdomain.com/auth/facebook/callback`.
   - Keep **Client OAuth Login** and **Web OAuth Login** enabled.
5. **App settings → Basic**: copy **App ID** and **App Secret** into `.env`
   (`FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET`), set `FACEBOOK_REDIRECT_URI`,
   and `FACEBOOK_AUTH_ENABLED=true`.
6. Permissions: the default `email` + `public_profile` are enough. `email` needs
   no App Review, but note **Facebook may still not return an email** (the user
   can decline, or the account may have only a phone) — our code handles that
   gracefully (see edge cases).
7. Flip the app from **Development** to **Live** (top toggle) before real users
   can authenticate.

Docs: [Facebook Login](https://developers.facebook.com/docs/facebook-login/).

---

## 5. Local Development Guide

- Socialite performs a **server-side** exchange, so `localhost` works without
  HTTPS. Register a local redirect URI (e.g. `http://localhost/auth/google/callback`
  or your Laragon host such as `http://gobuy.test/auth/google/callback`) in the
  provider console **in addition to** the production one.
- Set `APP_URL` correctly — `GOOGLE_REDIRECT_URI` in `.env.example` is derived
  from it (`${APP_URL}/auth/google/callback`).
- **Most common mistake — redirect URI mismatch.** The value in `.env` must match
  a registered URI **character-for-character**: scheme (`http` vs `https`), host,
  port, trailing slash, and `/auth/{provider}/callback` path all count. A mismatch
  shows as `redirect_uri_mismatch` (Google) or "URL Blocked" (Facebook).
- Staging: register the staging domain's callback URI too; providers allow many.

---

## 6. Deployment Guide

1. Set the production `.env`: real client id/secret, `*_AUTH_ENABLED=true`, and
   `*_REDIRECT_URI` using the **production HTTPS** domain.
2. **HTTPS is required in production** — providers reject non-HTTPS redirect URIs
   for live apps. Ensure `APP_URL` uses `https://`.
3. Add the production callback URIs in both provider consoles.
4. Clear & re-cache config:
   ```
   php artisan config:clear
   php artisan config:cache
   ```
   (Cached config is why a changed `.env` "does nothing" until you re-cache.)
5. Publish/Go-Live: Google **Publish App**, Facebook **Live** mode.

**Troubleshooting failed redirects**
| Symptom | Fix |
|---|---|
| `redirect_uri_mismatch` | Registered URI ≠ `*_REDIRECT_URI`. Make them identical. |
| Button missing | `*_AUTH_ENABLED` is not `true`, or config is cached — re-cache. |
| Route 404 | Provider disabled in `config/social.php`. |
| "URL Blocked" (Facebook) | Add the exact callback to Valid OAuth Redirect URIs. |
| Redirect works but no email | Provider returned no email — user sees a friendly prompt to use another method. |

---

## 7. Vendor Reuse Guide (for platform buyers)

> Audience: a Laravel developer who has never set up OAuth. You do **not** need to
> read the codebase.

**Enable Google or Facebook sign-in on your store:**
1. Get credentials — follow §3 (Google) and/or §4 (Facebook). You need a
   **Client ID/App ID**, a **Client Secret/App Secret**, and a **redirect URI** of
   the form `https://YOURSTORE.com/auth/google/callback`.
2. Put them in your `.env` (copy the block from `.env.example`).
3. **Enable** a provider by setting its `*_AUTH_ENABLED=true`. To **disable** one,
   set it to `false` — the button disappears and its routes 404. You can run with
   Google only, Facebook only, both, or neither.
4. Run `php artisan config:cache` and reload. The "Continue with …" buttons appear
   on the login, registration (and checkout) screens automatically.

**Best practices**
- Never commit real secrets; keep them in the server `.env`.
- Use HTTPS in production (providers require it).
- Test with the provider in "test users"/"development" mode before going live.

**Common errors:** see the §6 troubleshooting table.

---

## Email Verification Strategy

The existing **email/password + OTP** verification flow is **unchanged**. Social
sign-in integrates with it as follows:

| Scenario | Behaviour |
|---|---|
| Email/password registration | Issues a 6-digit OTP (unchanged). |
| **Google** sign-in | Google **always** verifies email → `email_verified_at` set, **OTP skipped**. |
| **Facebook** sign-in, email verified | Trusts the provider's per-user verified claim → OTP skipped. |
| **Facebook** sign-in, email **not** verified | Account created but **falls back to our OTP** flow. |
| Provider returns **no email** | Rejected with a friendly message — we never guess an identity. |
| Existing **unverified** password account signs in with a verified provider email | Linked, and the account is **marked verified** (clears the pending OTP wall). |
| Multiple providers on one account | Fully supported — each provider is one `social_accounts` row on the same user. |
| **Account-takeover prevention** | Linking to an existing account happens **only** on a provider-verified email, so an attacker presenting an unverified address can never claim a victim's account. |
| Guest → account | On any verified sign-in (OTP or social), past guest orders placed with the same email are adopted (`ClaimGuestOrders` on the `Verified` event). |

**Why this is the right balance (vs. Shopify / BigCommerce / WooCommerce):**
leading platforms treat a provider-verified email as proof of ownership and skip
their own verification step — exactly what we do for Google (and verified
Facebook). We are stricter than a naive integration on the one risky case
(Facebook without a verified email), where we fall back to OTP rather than trust
the address. This maximises conversion (one-tap, no verification email for the
common case) without weakening account security.

---

## Files

| Concern | Path |
|---|---|
| Provider registry | `config/social.php` |
| Credentials | `config/services.php` (`google`, `facebook`) |
| Identity model | `app/Modules/Customer/Models/SocialAccount.php` |
| Resolution service | `app/Modules/Customer/Services/SocialAuthService.php` |
| Controller | `app/Modules/Customer/Http/Controllers/Auth/SocialAuthController.php` |
| Guest-order merge | `app/Modules/Order/Listeners/ClaimGuestOrders.php` |
| Buttons UI | `resources/views/components/social-auth-buttons.blade.php` |
| Routes | `app/Modules/Customer/routes.php` (`social.redirect`, `social.callback`) |
| Migrations | `create_social_accounts_table`, `make_users_password_nullable` |
| Tests | `tests/Feature/Auth/SocialAuthTest.php` |
