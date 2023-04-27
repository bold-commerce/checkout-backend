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

    'bold_checkout' => [
        'client_id' => env('DEVELOPER_CLIENT_ID'),
        'client_secret' => env('DEVELOPER_CLIENT_SECRET'),
        'redirect_url' => env('DEVELOPER_REDIRECT_URL'),
        'assets_url' => env('ASSETS_URL', 'https://cashier.boldcommerce.com/assets/experience'),
        'checkout_url' => env('CHECKOUT_URL', 'https://cashier.boldcommerce.com'),
        'api_url' => env('CHECKOUT_API_URL', 'https://api.boldcommerce.com'),
        'api_path' => env('CHECKOUT_API_PATH', 'checkout'),
        'api_environment' => env('CHECKOUT_API_ENVIRONMENT', 'production'),
        'x_bold_proxy_auth_key' => env('X_BOLD_PROXY_AUTH_KEY'),
        'bugsnag_api_key' => env('BUGSNAG_API_KEY'),
    ],

    'bugsnag' => [
        'api_key' => env('BUGSNAG_API_KEY'),
    ],
];
