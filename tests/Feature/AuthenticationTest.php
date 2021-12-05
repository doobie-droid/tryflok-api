<?php

namespace Tests\Feature;

use App\Constants\Roles;
use App\Models\User;
use App\Notifications\User\EmailConfirmation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\MockData\User as UserMock;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_super_admin_login_works()
    {
        $response = $this->json('POST', '/api/v1/auth/login', UserMock::SEEDED_SUPER_ADMIN);
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'user' => [
                    'roles',
                ],
                'token',
            ]
        ])->assertJson([
            'data' => [
                'user' => [
                    'roles' => [
                        [
                            'name' => Roles::SUPER_ADMIN,
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_login_works()
    {
        $response = $this->json('POST', '/api/v1/auth/login', UserMock::SEEDED_ADMIN);
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'user' => [
                    'roles'
                ],
                'token',
            ]
        ])
        ->assertJson([
            'data' => [
                'user' => [
                    'roles' => [
                        [
                            'name' => Roles::ADMIN,
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function test_user_login_works()
    {
        $response = $this->json('POST', '/api/v1/auth/login', UserMock::SEEDED_USER);
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'user' => [
                    'roles',
                    'wallet',
                ],
                'token',
            ]
        ])
        ->assertJson([
            'data' => [
                'user' => [
                    'name' => UserMock::SEEDED_USER['name'],
                    'email' => UserMock::SEEDED_USER['email'],
                    'username' => UserMock::SEEDED_USER['username'],
                    'roles' => [
                        [
                            'name' => Roles::USER,
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function test_registration_works()
    {
        Notification::fake();
        $response = $this->json('POST', '/api/v1/auth/register', UserMock::UNSEEDED_USER);
        $response->assertStatus(200)
        ->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'user' => [
                    'roles',
                    'wallet',
                ],
                'token',
            ]
        ])
        ->assertJson([
            'data' => [
                'user' => [
                    'name' => UserMock::UNSEEDED_USER['name'],
                    'email' => UserMock::UNSEEDED_USER['email'],
                    'username' => UserMock::UNSEEDED_USER['username'],
                    'roles' => [
                        [
                            'name' => Roles::USER,
                        ]
                    ]
                ]
            ]
        ]);
        $user = User::where('email', UserMock::UNSEEDED_USER['email'])->first();
        Notification::assertSentTo(
            [$user],
            EmailConfirmation::class
        );

        $this->assertDatabaseHas('users', [
            'email' => UserMock::UNSEEDED_USER['email'],
            'name' => UserMock::UNSEEDED_USER['name'],
        ]);
    }

    public function test_invalid_usernames_are_not_registered()
    {
        $testData = UserMock::UNSEEDED_USER;

        $testData['username'] = 'e-rtra';
        $response = $this->json('POST', '/api/v1/auth/register', $testData);
        $response->assertStatus(400);

        $testData['username'] = 'user@';
        $response = $this->json('POST', '/api/v1/auth/register', $testData);
        $response->assertStatus(400);

        $testData['username'] = 'user#';
        $response = $this->json('POST', '/api/v1/auth/register', $testData);
        $response->assertStatus(400);
    }

    public function test_valid_usernames_are_registered()
    {
        $testData = UserMock::UNSEEDED_USER;

        $testData['username'] = 'the_user9';
        $testData['email'] = 'valid1@test.com';
        $response = $this->json('POST', '/api/v1/auth/register', $testData);
        $response->assertStatus(200);

        $testData['username'] = '9the_user9';
        $testData['email'] = 'valid2@test.com';
        $response = $this->json('POST', '/api/v1/auth/register', $testData);
        $response->assertStatus(200);

        $testData['username'] = '_9the_user9';
        $testData['email'] = 'valid3@test.com';
        $response = $this->json('POST', '/api/v1/auth/register', $testData);
        $response->assertStatus(200);
    }

    public function test_update_password_works()
    {
        $user = User::factory()->create();
        $this->be($user);
        $response = $this->json('PUT', '/api/v1/account/password', [
            'old' => 'password',
            'password' => 'user126',
            'password_confirmation' => 'user126',
        ]);
        $response->assertStatus(200);
        $this->assertTrue(Hash::check('user126', $user->password));
    }

    public function test_refresh_token_works()
    {
        $user = User::factory()->create();
        $this->be($user);
        $token = JWTAuth::fromUser($user);
        $server = [
            'HTTP_Authorization' => 'Bearer ' . $token
        ];
    
        $response = $this->json('PATCH', '/api/v1/account/token', [], $server);
        $response->assertStatus(200)->assertJsonStructure([
            'status_code',
            'message',
            'data' => [
                'user' => [
                    'roles',
                ],
                'token',
            ]
        ]);
    }
}
