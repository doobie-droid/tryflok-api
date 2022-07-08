<?php

namespace Tests\MockData;

class Review
{
    public static function generateGetReviewResponse(): array
    {
        return [
            'status_code',
            'message',
            'data' => [
                'reviews' => [
                        'data' => [
                            [
                            'user_id',
                            'id'
                            ] 
                        ]                        

                ],
            ],
        ];
    }
}