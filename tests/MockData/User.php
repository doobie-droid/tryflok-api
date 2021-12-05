<?php

namespace Tests\MockData;

class User
{
    const SEEDED_SUPER_ADMIN = [
        'name' => 'Super Admin',
        'username' => 'super_admin',
        'email' => 'user1@test.com',
        'password' => 'user123',
        'referral_id' => 'LLFFBE-210506',
    ];

    const SEEDED_ADMIN = [
        'name' => 'Flok Admin',
        'username' => 'flok_admin',
        'email' => 'user2@test.com',
        'password' => 'user123',
        'referral_id' => 'N6ACS0-210506',
    ];

    const SEEDED_USER = [
        'name' => 'User One',
        'username' => 'user_one',
        'email' => 'user3@test.com',
        'password' => 'user123',
        'referral_id' => 'OOG3D0-210506',
    ];

    const SEEDED_CREATOR = [
        'name' => 'Creator One',
        'username' => 'creator_one',
        'email' => 'user4@test.com',
        'password' => 'user123',
        'referral_id' => 'LKEJRK-210506',
    ];

    const SEEDED_CREATOR_2 = [
        'name' => 'Creator Two',
        'username' => 'creator_two',
        'email' => 'user6@test.com',
        'password' => 'user123',
        'referral_id' => 'THHJKB-210506',
    ];

    const SEEDED_CREATOR_3 = [
        'name' => 'Creator Three',
        'username' => 'creator_three',
        'email' => 'user7@test.com',
        'password' => 'user123',
        'referral_id' => 'THKJKB-210506',
    ];

    const SEEDED_CREATOR_4 = [
        'name' => 'Creator Four',
        'username' => 'creator_four',
        'email' => 'user8@test.com',
        'password' => 'user123',
        'referral_id' => '0HHJKB-210506',
    ];

    const UNSEEDED_USER = [
        'name' => 'Unseeded User One',
        'email' => 'user5@test.com',
        'username' => 'unseeded',
        'password' => 'user123',
        'password_confirmation' => 'user123',
        //'referral_id' => 'TLHJKB-210506',
    ];
}
