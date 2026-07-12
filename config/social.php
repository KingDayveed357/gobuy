<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Social login providers
    |--------------------------------------------------------------------------
    |
    | The single source of truth for social authentication. Routes, buttons and
    | the callback controller are all generated over the ENABLED providers here,
    | so adding a new provider (Apple, Microsoft, X, GitHub, …) is a config entry
    | + Socialite credentials in config/services.php — no new application code.
    |
    | Keys:
    |   enabled                 Toggle the provider on/off (env-driven).
    |   label                   Human name shown on the "Continue with …" button.
    |   icon                    Asset path for the button glyph (in public/).
    |   email_always_verified   TRUE when the provider guarantees a verified email
    |                           for every account (Google). When FALSE we only
    |                           trust the provider's per-user "verified" claim and
    |                           otherwise fall back to our own OTP verification.
    |
    */

    'providers' => [

        'google' => [
            'enabled' => env('GOOGLE_AUTH_ENABLED', false),
            'label' => 'Google',
            'icon' => 'theme/img/social/google.svg',
            'email_always_verified' => true,
        ],

        'facebook' => [
            'enabled' => env('FACEBOOK_AUTH_ENABLED', false),
            'label' => 'Facebook',
            'icon' => 'theme/img/social/facebook.svg',
            'email_always_verified' => false,
        ],

    ],

];
