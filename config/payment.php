<?php

return [
    'default' => 'test',
    'providers' => [
        'paystack' => [
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'allowed_ips' => env('PAYSTACK_ALLOWED_IPS'),
        ],
        'flutterwave' => [
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        ],
        'stripe' => [
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'public_key' => env('STRIPE_PUBLIC_KEY'),
        ],
        'apple' => [
            'secret_key' => env('APPLE_PAY_SECRET_KEY'),
            'public_key' => env('APPLE_PAY_PUBLIC_KEY'),
            'api_url' => env('APPLE_API_URL', 'https://buy.itunes.apple.com/'),
        ],
    ],
];