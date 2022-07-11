<?php

namespace Tests\MockData;

class Collection
{
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

    public static function generateGetAllResponse(): array
    {
        $expected_response_structure = self::STANDARD_RESPONSE;
        $content_types_available_key = array_search('content_types_available', $expected_response_structure['data']);
        unset($expected_response_structure['collection'][$content_types_available_key]);
        return $expected_response_structure;
    }

    const STANDARD_RESPONSE = [
        'data' => [
            'collection' => [
                'id',
                'title',
                'description',
                'user_id', // owner of collection
                'type', // book, series, channel, digiverse
                'is_available',
                'approved_by_admin',
                'show_only_in_collections',
                'views',
            ]
        ]       
    ];

}
