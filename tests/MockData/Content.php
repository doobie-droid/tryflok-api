<?php

namespace Tests\MockData;

class Content
{

    public static function generateStandardCreateResponse(): array
    {
        return [
            'status_code',
            'message',
            'data' => [
                'content' => self::STANDARD_STRUCTURE,
            ]
        ];
    }

    public static function generateLiveContentCreateResponse(): array
    {
        $structure = self::STANDARD_STRUCTURE;
        $metas_key = array_search('metas', $structure);
        unset($structure[$metas_key]);
        $structure['metas'] = [
            'channel_name',
            'rtc_token',
            'rtm_token',
            'join_count',
        ];

        return [
            'status_code',
            'message',
            'data' => [
                'content' => $structure,
            ]
        ];
    }

    public static function generateGetSingleContentResponse(): array
    {
        $structure = self::STANDARD_STRUCTURE;
        $structure[] = 'access_through_ancestors';
        $structure[] = 'userables';

        return [
            'status_code',
            'message',
            'data' => [
                'content' => $structure,
            ],
        ];
    }

    public static function generateGetDigiverseContentsResponse(): array
    {
        $structure = self::STANDARD_STRUCTURE;
        $structure[] = 'access_through_ancestors';
        $structure[] = 'userables';

        return [
            'status_code',
            'message',
            'data' => [
                'contents' => [
                    $structure,
                ],
                'current_page',
                'items_per_page',
                'total',
            ],
        ];
    }

    public static function generateGetAssetsResponse(): array
    {
        return [
            'status_code',
            'message',
            'data' => [
                'assets' => [
                    [
                        'url',
                        'id',
                    ]
                ],
                'cookies',
                'cookies_expire',
            ],
        ];
    }

    const STANDARD_STRUCTURE = [
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
        'revenues_count',
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
                'name',
            ]
        ],
        'metas',
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
