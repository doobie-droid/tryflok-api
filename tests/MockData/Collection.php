<?php

namespace Tests\MockData;

use App\Models;

class Collection
{
    public static function generateStandardCreateRequest(): array
    {
        $request = self::STANDARD_REQUEST;
        $request['cover']['asset_id'] = Models\Asset::factory()->create()->id;
        $request['tags'][] = Models\Tag::factory()->create()->id;
        return $request;
    }

    public static function generateCollectionCreatedResponse(): array
    {
        $expected_response_structure = self::STANDARD_RESPONSE;
        unset($expected_response_structure['data']['collection']['userables']);
        $content_types_available_key = array_search('content_types_available', $expected_response_structure['data']['collection']);
        unset($expected_response_structure['data']['collection'][$content_types_available_key]);
        return $expected_response_structure;
    }

    const SEEDED_UNPAID_COLLECTION = [
        'public_id' => '1867378820606c9e5ab27c6',
        'title' => 'Test Collection',
        'description' => 'a test collection',
        'user_id' => 4,
        'type' => 'book',
        'is_available' => 1,
    ];

    const SEEDED_UNPAID_COLLECTION2 = [
        'public_id' => '1867378820606c9e5ab27d6',
        'title' => 'New Collection',
        'description' => 'a test collection',
        'user_id' => 4,
        'type' => 'book',
        'is_available' => 1,
    ];

    const SEEDED_COLLECTION_WITH_SUB = [
        'public_id' => 'sub1867378820606c9e5ab27d6',
        'title' => 'Parent Collection With Sub',
        'description' => 'a test collection',
        'user_id' => 4,
        'type' => 'book',
        'is_available' => 1,
    ];

    const SEEDED_SUB_COLLECTION_1_LEVEL_1 = [
        'public_id' => 'sub1l1p867378820606c9e5',
        'title' => 'Sub 1 Level 1',
        'description' => 'a test collection',
        'user_id' => 4,
        'type' => 'book',
        'is_available' => 1,
    ];

    const SEEDED_SUB_COLLECTION_1_LEVEL_1_CHILD_1 = [
        'public_id' => 'sub1l21p867378820606c9e5',
        'title' => 'Sub 1 Level 1 Child 1',
        'description' => 'a test collection',
        'user_id' => 4,
        'type' => 'book',
        'is_available' => 1,
    ];

    const SEEDED_SUB_COLLECTION_1_LEVEL_1_CHILD_2 = [
        'public_id' => 'sub1l22p867378820606c9e5',
        'title' => 'Sub 1 Level 1 Child 2',
        'description' => 'a test collection',
        'user_id' => 4,
        'type' => 'book',
        'is_available' => 1,
    ];

    const SEEDED_SUB_COLLECTION_2_LEVEL_1 = [
        'public_id' => 'sub1l2p867378820606c9e5',
        'title' => 'Sub 2 Level 1',
        'description' => 'a test collection',
        'user_id' => 4,
        'type' => 'book',
        'is_available' => 1,
    ];

    const SEEDED_SUB_COLLECTION_2_LEVEL_1_CHILD_1 = [
        'public_id' => 'sub2l21p867378820606c9e5',
        'title' => 'Sub 2 Level 1 Child 1',
        'description' => 'a test collection',
        'user_id' => 4,
        'type' => 'book',
        'is_available' => 1,
    ];

    const SEEDED_SUB_COLLECTION_2_LEVEL_1_CHILD_2 = [
        'public_id' => 'sub2l22p867378820606c9e5',
        'title' => 'Sub 2 Level 1 Child 2',
        'description' => 'a test collection',
        'user_id' => 4,
        'type' => 'book',
        'is_available' => 1,
    ];

    const STANDARD_REQUEST = [
        'digiverse_id' => '',
        'title' => 'The first Collection',
        'description' => 'Testing collection creation',
        'price' => [
            'amount' => 100,
            'interval' => 'one-off',
            'interval_amount' => 1,
        ],
        'tags' => [],
        'cover' => [
            'asset_id' => '',
        ],
    ];

    const STANDARD_RESPONSE = [
        'status_code',
        'message',
        'data' => [
            'collection' => [
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
                'revenues_count',
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
                        'interval_amount',
                    ],
                ],
                'tags' => [
                    [
                        'id',
                        'name',
                    ],
                ],
                'userables' => [
                    [
                        'userable_type',
                    ],
                ],
                'parent_collections' => [
                [
                    'id',
                    'title',
                    'description',
                    'user_id',
                    'type',
                    'is_available',
                    'approved_by_admin',
                    'show_only_in_collections',
                    'views',
                    'deleted_at',
                    'created_at',
                    'updated_at',
                    'trending_points',
                    'is_adult',
                    'is_challenge',
                    'archived_at',
                    'pivot' => [
                        'child_id',
                        'parent_id'
                    ],
                ],
            ],
            ],
        ]
    ];
}
