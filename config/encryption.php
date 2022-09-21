<?php

return [
    'symmetrical' => [
        'key' => env('SYMMETRICAL_ENCRYPTION_KEY'),
    ],
    'asymmetrical' => [
        'private_key' => env('ASYMMETRICAL_PRIVATE_KEY'),
        'public_key' => env('ASYMMETRICAL_PUBLIC_KEY'),
    ],
];
