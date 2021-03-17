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
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'hubspot' => [
        'url' => env('HUBSPOT_URL'),
        'app_id' => env('HUBSPOT_APP_ID'),
        'client_id' => env('HUBSPOT_CLIENT_ID'),
        'client_secret' => env('HUBSPOT_CLIENT_SECRET'),
    ],

    'gg_key' => [
        'project_id' => env('PROJECT_ID'),
        'private_key_id' => env('PRIVATE_KEY_ID'),
        'private_key' => env('PRIVATE_KEY'),
        'client_email' => env('CLIENT_EMAIL'),
        'client_id' => env('CLIENT_ID')
    ],
];
