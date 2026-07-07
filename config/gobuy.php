<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Delivery
    |--------------------------------------------------------------------------
    |
    | Flat delivery fee (in Naira) applied at checkout. Tiered delivery zones
    | are a planned enhancement; for MVP a single flat fee keeps it simple.
    |
    */

    'delivery_fee' => (float) env('GOBUY_DELIVERY_FEE', 1500),

    /*
    |--------------------------------------------------------------------------
    | VAT
    |--------------------------------------------------------------------------
    |
    | The default VAT rate (percent) applied to new products. Nigeria's
    | standard rate is 7.5%. Each product may override this and may be flagged
    | tax-exempt. This is the single source of truth for the default.
    |
    */

    'vat_rate' => (float) env('GOBUY_VAT_RATE', 7.5),

    /*
    |--------------------------------------------------------------------------
    | Homepage merchandising cache
    |--------------------------------------------------------------------------
    |
    | Seconds to cache the resolved homepage sections. Invalidated automatically
    | when sections, collections or banners change. Set to 0 to disable (the test
    | suite does, for deterministic assertions).
    |
    */

    'homepage_cache_ttl' => (int) env('GOBUY_HOMEPAGE_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    |
    | How long (minutes) an add-to-cart stock reservation is held before it
    | expires and the stock is released back to other shoppers.
    |
    */

    'reservation_ttl_minutes' => (int) env('GOBUY_RESERVATION_TTL', 30),

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    |
    | Pay on Delivery (POD) is offered to retail customers whose order subtotal
    | is at or below the threshold (in Naira). Manual bank transfers are paid
    | into the account below and reconciled by an admin from a proof of payment.
    |
    */

    'pod' => [
        'enabled' => (bool) env('GOBUY_POD_ENABLED', true),
        'threshold' => (float) env('GOBUY_POD_THRESHOLD', 150000), // Naira
    ],

    'bank_account' => [
        'bank' => env('GOBUY_BANK_NAME', 'Zenith Bank'),
        'account_name' => env('GOBUY_BANK_ACCOUNT_NAME', 'GoBuy Commerce Ltd'),
        'account_number' => env('GOBUY_BANK_ACCOUNT_NUMBER', '1012345678'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Messaging (SMS / WhatsApp)
    |--------------------------------------------------------------------------
    |
    | Customer order/delivery updates are sent over the configured channel.
    | "log" (default) writes to the application log — safe for dev and tests.
    | "whatsapp" uses the WhatsApp Cloud API; "sms" uses an HTTP SMS gateway.
    | No extra Composer packages are required (all calls use the HTTP client).
    |
    */

    'messaging' => [
        'driver' => env('GOBUY_MESSAGING_DRIVER', 'log'), // log | whatsapp | sms

        'whatsapp' => [
            'base_url' => env('GOBUY_WHATSAPP_BASE_URL', 'https://graph.facebook.com/v20.0'),
            'phone_number_id' => env('GOBUY_WHATSAPP_PHONE_ID'),
            'token' => env('GOBUY_WHATSAPP_TOKEN'),
        ],

        'sms' => [
            'base_url' => env('GOBUY_SMS_BASE_URL'),
            'api_key' => env('GOBUY_SMS_API_KEY'),
            'sender_id' => env('GOBUY_SMS_SENDER_ID', 'GoBuy'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Returns
    |--------------------------------------------------------------------------
    |
    | Default return policy. Products may override the window via
    | `return_window_days`, and `is_returnable=false` opts a product out
    | entirely. The window clock starts at the order's `delivered_at`.
    |
    */

    'returns' => [
        'window_days' => (int) env('GOBUY_RETURN_WINDOW_DAYS', 14),
        'default_destination' => env('GOBUY_RETURN_DEFAULT_DESTINATION', 'store_credit'), // store_credit | original
        'store_credit_expiry_months' => (int) env('GOBUY_STORE_CREDIT_EXPIRY_MONTHS', 12),
        'carrier' => env('GOBUY_RETURN_CARRIER', 'GoBuy Returns'),
        'dropoff_address' => env('GOBUY_RETURN_ADDRESS', 'GoBuy Returns Centre, Port Harcourt, Rivers State'),

        // Fraud scoring (0–100) signal thresholds.
        'fraud' => [
            'high_value_naira' => (int) env('GOBUY_RETURN_HIGH_VALUE', 100000),
            'new_account_days' => 7,
            'lookback_days' => 90,
        ],

        // Rule-based auto-approval. A low-risk, low-value, "soft" return is
        // approved without a human; everything else routes to manual review.
        'auto_approve' => [
            'enabled' => (bool) env('GOBUY_RETURN_AUTO_APPROVE', true),
            'max_score' => (int) env('GOBUY_RETURN_AUTO_MAX_SCORE', 40),
            'max_value_naira' => (int) env('GOBUY_RETURN_AUTO_MAX_VALUE', 50000),
            'reasons' => ['changed_mind', 'better_price'],
        ],

        // Order statuses from which a return may be requested.
        'eligible_order_statuses' => ['delivered', 'completed'],

        // Category slugs that are never returnable (perishables, digital, etc.).
        'excluded_category_slugs' => [],
    ],

];
