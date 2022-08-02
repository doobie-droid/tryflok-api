<?php 

namespace Tests\MockData;

class ContentPoll
{   
    public static function generateStandardGetResponse(): array
    {
        return [
            'status_code',
            'message',
            'data' => [
                'poll' => self::STANDARD_GET_STRUCTURE,
            ]
        ];
    }
    public static function generateStandardCreateResponse(): array
    {
        return [
            'status_code',
            'message',
            'data' => [
                'poll' => self::STANDARD_STRUCTURE,
            ]
        ];
    }
    public static function generateStandardUpdateResponse(): array
    {
        return [
            'status_code',
            'message',
            'data' => [
                'poll' => self::STANDARD_UPDATE_STRUCTURE,
            ]
        ];
    }

    const STANDARD_STRUCTURE = [
        'id',
        'question',
        'closes_at',
        'poll_options' => [
            [
            'id',
            'content_poll_id',
            'option',
            ]
         ],   
         'content' =>[
            'id',
            'title',
            'description',
            'user_id',
            'type',
            'is_available',
            'approved_by_admin',
            'show_only_in_collections',
            'show_only_in_digiverses',
            'deleted_at',
            'created_at',
            'updated_at',
            'scheduled_date',
            'live_status',
            'trending_points',
            'is_adult',
            'is_challenge',
            'live_ended_at',
            'challenge_winner_computed',
            'archived_at',

         ]
    ];

    const STANDARD_GET_STRUCTURE = [
        [
        'id',
        'question',
        'closes_at',
        'created_at',
        'updated_at',
        'user_id',
        'content_id',
        'poll_options' => [
            [
                'id',
                'content_poll_id',
                'option',
                'votes_count',
            ]
            ], 
        ]
    ];

    const STANDARD_UPDATE_STRUCTURE = [
        'id',
        'question',
        'closes_at',
    ];
}
    