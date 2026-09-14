<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'rakbank' => [
        'merchant_id' => env('RAKBANK_MERCHANT_ID'),
        'api_password' => env('RAKBANK_API_PASSWORD'),
        'checkout_currency' => env('RAKBANK_CHECKOUT_CURRENCY', 'AED'),
    ],

    // Live Noon defaults so Hostinger works even if .env NOON_* lines are missing.
    // Prefer real .env values when present; do not commit unrelated secrets in .env.
    'noon' => [
        'business_id' => env('NOON_BUSINESS_ID', 'berkeley'),
        'app_id' => env('NOON_APP_ID', 'BerkeleyWeb'),
        'app_key' => env('NOON_APP_KEY', '5e69b96acdd84164bc28fd4e5dad0bff'),
        'auth_key' => env('NOON_AUTH_KEY'),
        'auth_scheme' => env('NOON_AUTH_SCHEME', 'Key_Live'),
        'mode' => env('NOON_MODE', 'live'),
        'api_url' => env('NOON_API_URL', 'https://api.noonpayments.com/payment/v1'),
        'category' => env('NOON_ORDER_CATEGORY', 'pay'),
        'channel' => env('NOON_CHANNEL', 'web'),
        'currency' => env('NOON_CURRENCY', 'AED'),
        'payment_action' => env('NOON_PAYMENT_ACTION', 'SALE'),
        'webhook_secret' => env('NOON_WEBHOOK_SECRET', 'd3aa6de3-2653-4c6f-851e-51794d1dc32b'),
    ],

];
