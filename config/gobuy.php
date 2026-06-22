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

];
