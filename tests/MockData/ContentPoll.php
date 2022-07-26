<?php 

namespace Tests\MockData;

class ContentPoll
{
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
}
    