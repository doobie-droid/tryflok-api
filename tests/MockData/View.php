<?php

namespace Tests\MockData;

use App\Models;

class View
{
    public static function generateStandardAddViewRequest(): array
    {
        $request = self::STANDARD_REQUEST;
        $request['content']['content_id'] = Models\View::factory()->create()->id;
        return $request;
    }

    public static function generateAddViewResponse(): array
    {
        return [
            'status_code',
            'message',
            'data' => [
                'views' => [
                        'data' => [
                            [
                            'user_id',
                            'id',
                            'viewable_type',
                            ] 
                        ]                        

                ],
            ],
        ];
    }
    public const STANDARD_REQUEST = [
        'id',
        'user_id',
        'viewable_type',
        'viewable_id',
    ];
}