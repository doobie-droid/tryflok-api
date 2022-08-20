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

    public static function generateChallengeContentCreateResponse(): array
    {
        $structure = self::STANDARD_STRUCTURE;
        $metas_key = array_search('metas', $structure);
        unset($structure[$metas_key]);
        $structure['metas'] = [
            'channel_name',
            'rtc_token',
            'rtm_token',
            'join_count',
            'pot_size',
            'minimum_contribution',
            'moderator_share',
            'winner_share',
            'loser_share',
        ];
        
        $structure['challenge_contestants'] = [
            [
                'id',
                'status',
                'contestant' => [
                    'id',
                    'name',
                    'email',
                    'username',
                    'profile_picture',
                ]
            ],
        ];

        $structure['live_broadcasters'] = [
            [
                'id',
                'agora_uid',
                'video_stream_status',
                'audio_stream_status',
                'broadcaster' => [
                    'id',
                    'name',
                    'email',
                    'username',
                    'profile_picture',
                ]
            ],
        ];

        $structure['voting_result'] = [
            'total_votes',
            'contestants' => [
                [
                    'contestant' => [
                        'id',
                        'name',
                        'email',
                        'username',
                        'profile_picture',
                    ],
                    'votes',
                ],
            ]
        ];

        $structure[] = 'total_challenge_contributions';
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
                    ],
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
        'live_ended_at',
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
        'likes_count',
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
                'interval_amount',
            ],
        ],
        'tags' => [
            [
                'id',
                'name',
            ],
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
            ],
        ]
    ];
}
