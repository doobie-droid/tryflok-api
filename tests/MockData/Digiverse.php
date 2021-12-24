<?php

namespace Tests\MockData;

class Digiverse
{
    const UNSEEDED_DIGIVERSE = [
        'title' => 'The first Digiverse',
        'description' => 'Testing digiverse creation',
        'price' => [
            'amount' => 100,
            'interval' => 'monthly',
            'interval_amount' => 1,
        ],
        'tags' => [
            '0e14760d-1d41-45aa-a820-87d6dc35f7ff',
            '120566de-0361-4d66-b458-321d4ede62a9'
        ],
        'cover' => [
            'asset_id' => '',
        ],
    ];

    const STANDARD_DIGIVERSE_RESPONSE = [
        'status_code',
        'message',
        'data' => [
            'digiverse' => [
                'id',
                'title',
                'description',
                'owner' => [
                    'id',
                    'name',
                    'email',
                    'username',
                ],
                'type',
                'is_available',
                'approved_by_admin',
                'show_only_in_collections',
                'views',
                'subscriptions_count',
                'ratings_count',
                'ratings_average',
                'cover' => [
                    'url',
                    'asset_type',
                    'encryption_key',
                ],
                'prices' => [
                    [
                        'id',
                        'amount',
                        'currency',
                        'interval',
                        'interval_amount'
                    ]
                ],
                'tags' => [
                    [
                        'id',
                        'type',
                        'name',
                    ]
                ],
            ]
        ]
    ];

    const STANDARD_DIGIVERSE_RESPONSE_WITH_CONTENTS = [
        'status_code',
        'message',
        'data' => [
            'digiverse' => [
                'id',
                'title',
                'description',
                'owner' => [
                    'id',
                    'name',
                    'email',
                    'username',
                ],
                'type',
                'is_available',
                'approved_by_admin',
                'show_only_in_collections',
                'views',
                'subscriptions_count',
                'ratings_count',
                'ratings_average',
                'cover' => [
                    'url',
                    'asset_type',
                    'encryption_key',
                ],
                'prices' => [
                    [
                        'id',
                        'amount',
                        'currency',
                        'interval',
                        'interval_amount'
                    ]
                ],
                'tags' => [
                    [
                        'id',
                        'type',
                        'name',
                    ]
                ],
                'contents' => [
                    [
                        'id',
                        'type',
                    ]
                ]
            ]
        ]
    ];
}
