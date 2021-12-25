<?php

namespace Tests\MockData;
use App\Models;

class Digiverse
{
    public static function generateStandardCreateRequest(): array
    {
        $request = self::STANDARD_REQUEST;
        $request['cover']['asset_id'] = Models\Asset::factory()->create()->id;
        $request['tags'][] =  Models\Tag::factory()->create()->id;
        return $request;
    }

    public static function generateStandardUpdateRequest(): array
    {
        $request = self::STANDARD_REQUEST;
        $request['cover']['asset_id'] = Models\Asset::factory()->create()->id;
        $request['tags'][] =  [
            'action' => 'add',
            'id' => Models\Tag::factory()->create()->id,
        ];
        return $request;
    }

    public static function generateDigiverseCreatedResponse(): array
    {
        $expected_response_structure = self::STANDARD_RESPONSE;
        unset($expected_response_structure['data']['digiverse']['userables']);
        $content_types_available_key = array_search('content_types_available', $expected_response_structure['data']['digiverse']);
        unset($expected_response_structure['data']['digiverse'][$content_types_available_key]);
        return $expected_response_structure;
    }

    public static function generateDigiverseUpdatedResponse(): array
    {
        $expected_response_structure = self::STANDARD_RESPONSE;
        unset($expected_response_structure['data']['digiverse']['userables']);
        $content_types_available_key = array_search('content_types_available', $expected_response_structure['data']['digiverse']);
        unset($expected_response_structure['data']['digiverse'][$content_types_available_key]);
        return $expected_response_structure;
    }

    const STANDARD_REQUEST = [
        'title' => 'The first Digiverse',
        'description' => 'Testing digiverse creation',
        'price' => [
            'amount' => 100,
            'interval' => 'monthly',
            'interval_amount' => 1,
        ],
        'tags' => [
        ],
        'cover' => [
            'asset_id' => '',
        ],
    ];

    const STANDARD_RESPONSE = [
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
                    'profile_picture',
                ],
                'type',
                'is_available',
                'approved_by_admin',
                'show_only_in_collections',
                'content_types_available',
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
                'userables' => [
                    [
                        'userable_type',
                    ]
                ]
            ]
        ]
    ];
}
