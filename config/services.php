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

    'noon' => [
        'business_id' => env('NOON_BUSINESS_ID'),
        'app_id' => env('NOON_APP_ID'),
        'app_key' => env('NOON_APP_KEY'),
        'auth_key' => env('NOON_AUTH_KEY'),
        'auth_scheme' => env('NOON_AUTH_SCHEME'),
        'mode' => env('NOON_MODE', 'test'),
        'api_url' => env('NOON_API_URL', 'https://api-test.noonpayments.com/payment/v1'),
        'category' => env('NOON_ORDER_CATEGORY', 'pay'),
        'channel' => env('NOON_CHANNEL', 'web'),
        'currency' => env('NOON_CURRENCY', 'AED'),
        'payment_action' => env('NOON_PAYMENT_ACTION', 'SALE'),
        'webhook_secret' => env('NOON_WEBHOOK_SECRET'),
    ],

];
