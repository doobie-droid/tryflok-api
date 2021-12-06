<?php

namespace Tests\MockData;

class Content
{
    const SEEDED_UNPAID_ONE_OFF_VIDEO = [
        'public_id' => '826963893606c9de73bc36',
        'title' => 'Unpaid One-Off Video Content',
        'summary' => 'unpaid',
        'user_id' => 4,
        'type' => 'video',
        'language_id' => 40,
        'is_available' => 1,
    ];

    const SEEDED_UNPAID_ONE_OFF_VIDEO_2 = [
        'public_id' => '826963893606c9de73dc36',
        'title' => 'New One-Off Video Content',
        'summary' => 'unpaid',
        'user_id' => 4,
        'type' => 'video',
        'language_id' => 40,
        'is_available' => 1,
    ];

    const SEEDED_UNPAID_SUBSCRIPTION_AUDIO = [
        'public_id' => '678606098606ca378009e0',
        'title' => 'Subscription Audio Content',
        'summary' => 'unpaid subscription',
        'user_id' => 4,
        'type' => 'audio',
        'language_id' => 40,
        'is_available' => 1,
    ];

    const SEEDED_UNPAID_SUBSCRIPTION_AUDIO2 = [
        'public_id' => '678606098606aa378009e0',
        'title' => 'New Subscription Audio',
        'summary' => 'unpaid subscription audio',
        'user_id' => 4,
        'type' => 'audio',
        'language_id' => 40,
        'is_available' => 1,
    ];

    const SEEDED_UNPAID_PDF_BOOK = [
        'public_id' => '18240891946066d41d60016',
        'title' => 'Unpaid PDF Book Content',
        'summary' => 'unpaid PDF',
        'user_id' => 4,
        'type' => 'book',
        'language_id' => 40,
        'is_available' => 1,
    ];

    const SEEDED_UNPAID_PDF_BOOK2 = [
        'public_id' => '18240891946066e41d60016',
        'title' => 'New PDF Book',
        'summary' => 'unpaid PDF',
        'user_id' => 4,
        'type' => 'book',
        'language_id' => 40,
        'is_available' => 1,
    ];

    const SEEDED_UNPAID_IMAGE_BOOK = [
        'public_id' => '14517881306066d47a53205',
        'title' => 'Unpaid Image Book Content',
        'summary' => 'unpaid Image Book',
        'user_id' => 4,
        'type' => 'book',
        'language_id' => 40,
        'is_available' => 1,
    ];

    const SEEDED_UNPAID_IMAGE_BOOK2 = [
        'public_id' => '14517881306066e47a53205',
        'title' => 'New Image Book',
        'summary' => 'unpaid Image Book',
        'user_id' => 4,
        'type' => 'book',
        'language_id' => 40,
        'is_available' => 1,
    ];

    const STANDARD_CONTENT_RESPONSE = [
        'status_code',
        'message',
        'data' => [
            'content' => [
                'id',
                'title',
                'description',
                'live_status',
                'scheduled_date',
                'owner' => [
                    'id',
                    'name',
                    'email',
                    'username',
                ],
                'type',
                'is_available',
                'approved_by_admin',
                'show_only_in_digiverses',
                'ratings_count',
                'ratings_average',
                'subscribers_count',
                'views_count',
                'cover' => [
                    'url',
                    'asset_type',
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
                'assets' => [
                    [
                        'url',
                        'asset_type',
                        'encryption_key',
                        'resolutions'
                    ]
                ],
                'metas',
            ],
        ],
    ];

    const CONTENT_WITH_NO_ASSET_RESPONSE = [
        'status_code',
        'message',
        'data' => [
            'content' => [
                'id',
                'title',
                'description',
                'live_status',
                'scheduled_date',
                'owner' => [
                    'id',
                    'name',
                    'email',
                    'username',
                ],
                'type',
                'is_available',
                'approved_by_admin',
                'show_only_in_digiverses',
                'ratings_count',
                'ratings_average',
                'subscribers_count',
                'views_count',
                'cover' => [
                    'url',
                    'asset_type',
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
                'metas',
            ],
        ],
    ];

    const CONTENT_WITH_NO_COVER_AND_ASSET_RESPONSE = [
        'status_code',
        'message',
        'data' => [
            'content' => [
                'id',
                'title',
                'description',
                'live_status',
                'scheduled_date',
                'owner' => [
                    'id',
                    'name',
                    'email',
                    'username',
                ],
                'type',
                'is_available',
                'approved_by_admin',
                'show_only_in_digiverses',
                'ratings_count',
                'ratings_average',
                'subscribers_count',
                'views_count',
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
                'metas',
            ],
        ],
    ];

    const LIVE_CONTENT_RESPONSE = [
        'status_code',
        'message',
        'data' => [
            'content' => [
                'id',
                'title',
                'description',
                'live_status',
                'scheduled_date',
                'owner' => [
                    'id',
                    'name',
                    'email',
                    'username',
                ],
                'type',
                'is_available',
                'approved_by_admin',
                'show_only_in_digiverses',
                'ratings_count',
                'ratings_average',
                'subscribers_count',
                'views_count',
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
                'metas' => [
                    'channel_name',
                    'rtc_token',
                    'rtm_token',
                    'join_count',
                ],
            ],
        ],
    ];

    const ISSUE_STRUCTURE = [
        'id',
        'title',
        'description',
    ];

    const STANDARD_ISSUE_RESPONSE = [
        'status_code',
        'message',
        'data' => [
            'issue' => [
                'id',
                'title',
                'description',
            ]
        ]
    ];
}
