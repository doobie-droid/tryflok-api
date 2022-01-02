<?php

namespace Tests\MockData;

use App\Models;

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
        'referral_id' => 'TLHJKB-210506',
    ];

    const REGISTRATION_REQUEST = [
        'name' => 'Test User',
        'email' => 'user5@test.com',
        'username' => 'test_user',
        'password' => 'user123',
        'password_confirmation' => 'user123',
    ];

    const STANDARD_USER_RESPONSE_STRUCTURE = [
        'status_code',
        'message',
        'data' => [
            'user' => [
                'roles',
                'wallet',
            ],
            'token',
        ]
    ];

    public static function generateStandardUserResponseJson($name, $email, $username, $roles = [])
    {
        $response = [
            'data' => [
                'user' => [
                    'name' => $name,
                    'email' => $email,
                    'username' => $username,
                    'roles' => [],
                ],
            ],
        ];

        foreach ($roles as $role) {
            $response['data']['user']['roles'][] = [
                'name' => $role,
            ];
        }
        return $response;
    }

    public static function generateListUsersResponse()
    {
        $structure = self::STANDARD_STRUCTURE;
        unset($structure['wallet']);
        $response = [
            'status_code',
            'message',
            'data' => [
                'users' => [
                    $structure,
                ]
            ],
        ];

        return $response;
    }

    public const STANDARD_STRUCTURE = [
        'id',
        'name',
        'username',
        'email',
        'bio',
        'dob',
        'referral_id',
        'roles',
        'followers_count',
        'following_count',
        'followers',
        'following',
        'digiverses_created_count',
        'profile_picture' => [
            'url',
        ],
        'wallet' => [
            'balance',
        ]
    ];
}
