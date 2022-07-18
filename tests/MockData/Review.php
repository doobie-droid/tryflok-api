<?php

namespace Tests\MockData;

use App\Models;

class Review
{
public static function generatelistReviewResponse(): array
{
        return [
        'status_code',
        'message',
        'data' => [
            'reviews' => [
                'data' => [
                    '0'=>[
                        'id',
                        'user_id',
                        'rating',
                        'comment',
                        'reviewable_id',
                        'reviewable_type',
                    ],
                
            ],
        ],
        ],
    ];
    }
}