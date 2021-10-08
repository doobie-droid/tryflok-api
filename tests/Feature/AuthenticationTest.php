<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\MockData\User as UserMock;
use Tests\TestCase;
use App\Constants\Roles;
use App\Models\User;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;
    public function testSuperAdminLoginWorks()
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

    public function testAdminLoginWorks()
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

    public function testUserLoginWorks()
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

    public function testRegistrationWorks()
    {
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

        $this->assertDatabaseHas('users', [
            'email' => UserMock::UNSEEDED_USER['email'],
            'name' => UserMock::UNSEEDED_USER['name'],
        ]);

    }

    public function testUpdatePasswordWorks()
    {
        $user = User::where('email', UserMock::SEEDED_USER['email'])->first();
        $this->be($user);
        $response = $this->json('PUT', '/api/v1/account/password', [
            'old' => UserMock::SEEDED_USER['password'],
            'password' => 'user126',
            'password_confirmation' => 'user126',
        ]);
        $response->assertStatus(200);
        $this->assertTrue(Hash::check('user126', $user->password));
    }
}
