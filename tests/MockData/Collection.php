<?php

namespace Tests\MockData;

class Collection {
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
}