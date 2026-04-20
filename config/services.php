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

    'google_sheets' => [
        'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH', 'storage/app/private/google/ordermade-google-service-account.json'),
        'spreadsheet_id' => env('GOOGLE_ORDER_SPREADSHEET_ID', '1HnGYC5vPhHhT64QT4xsgeb7Rh7Oqva3DCZXxMGkCH68'),
        'sheet_gid' => env('GOOGLE_ORDER_SPREADSHEET_GID', 0),
        'start_range' => env('GOOGLE_ORDER_SPREADSHEET_RANGE', 'B6:D20'),
        'user_aliases' => is_array($aliases = json_decode(env('GOOGLE_ORDER_USER_ALIASES_JSON', '{}'), true)) ? $aliases : [],
    ],

];
