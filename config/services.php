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

    'cloudfront' => [
        'key_id' => env('AWS_CLOUDFRONT_KEY_ID'),
        'private_key' => env('AWS_CLOUDFRONT_PRIVATE_KEY'),
        'public_url' => env('PUBLIC_AWS_CLOUDFRONT_URL'),
        'private_url' => env('PRIVATE_AWS_CLOUDFRONT_URL'),
    ],

    'google' => [
        'ios_client_id' => env('GOOGLE_IOS_CLIENT_ID'),
        'android_client_id' => env('GOOGLE_ANDROID_CLIENT_ID'),
        'web_client_id' => env('GOOGLE_WEB_CLIENT_ID'),
        'fcm_server_key' => env('FCM_SERVER_KEY'),
    ],

    'apple' => [
        'pay_secret_key' => env('APPLE_PAY_SECRET_KEY'),
        'pay_public_key' => env('APPLE_PAY_PUBLIC_KEY'),
        'api_url' => env('APPLE_API_URL'),
    ],

    'agora' => [
        'id' => env('AGORA_APP_ID'),
        'certificate' => env('AGORA_APP_CERTIFICATE'),
    ],

];
