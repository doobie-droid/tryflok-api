<?php

namespace Tests\Feature\Controllers\API\V1\Auth\AuthController;

use App\Constants;
use App\Models;
use App\Notifications\User\EmailConfirmation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\MockData;

class RegisterTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function test_registration_without_referrer_works()
    {
        Notification::fake();
        $response = $this->json('POST', '/api/v1/auth/register', MockData\User::REGISTRATION_REQUEST);
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
                    'name' => MockData\User::REGISTRATION_REQUEST['name'],
                    'email' => MockData\User::REGISTRATION_REQUEST['email'],
                    'username' => MockData\User::REGISTRATION_REQUEST['username'],
                    'roles' => [
                        [
                            'name' => Constants\Roles::USER,
                        ]
                    ]
                ]
            ]
        ]);
        $user = Models\User::where('email', MockData\User::REGISTRATION_REQUEST['email'])->first();
        Notification::assertSentTo(
            [$user],
            EmailConfirmation::class
        );

        $this->assertDatabaseHas('users', [
            'email' => MockData\User::REGISTRATION_REQUEST['email'],
            'name' => MockData\User::REGISTRATION_REQUEST['name'],
        ]);
    }

    public function test_registration_with_referrer_works()
    {
        $user = Models\User::factory()->create();
        $request = MockData\User::REGISTRATION_REQUEST;
        $request['referral_id'] = $user->referral_id;
        Notification::fake();
        $response = $this->json('POST', '/api/v1/auth/register', $request);
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
                    'name' => $request['name'],
                    'email' => $request['email'],
                    'username' => $request['username'],
                    'roles' => [
                        [
                            'name' => Constants\Roles::USER,
                        ]
                    ]
                ]
            ]
        ]);
        $user2 = Models\User::where('email', $request['email'])->first();
        Notification::assertSentTo(
            [$user2],
            EmailConfirmation::class
        );

        $this->assertDatabaseHas('users', [
            'email' => $request['email'],
            'name' => $request,
            'referrer_id' => $user->id,
        ]);
    }

    public function test_invalid_usernames_are_not_registered()
    {
        $request = MockData\User::REGISTRATION_REQUEST;

        $request['username'] = 'e-rtra';
        $response = $this->json('POST', '/api/v1/auth/register', $request);
        $response->assertStatus(400);

        $request['username'] = 'user@';
        $response = $this->json('POST', '/api/v1/auth/register', $request);
        $response->assertStatus(400);

        $request['username'] = 'user#';
        $response = $this->json('POST', '/api/v1/auth/register', $request);
        $response->assertStatus(400);
    }

    public function test_valid_usernames_are_registered()
    {
        $request = MockData\User::REGISTRATION_REQUEST;

        $request['username'] = 'the_user9';
        $request['email'] = $this->faker->unique()->safeEmail;
        $response = $this->json('POST', '/api/v1/auth/register', $request);
        $response->assertStatus(200);

        $request['username'] = '9the_user9';
        $request['email'] = $this->faker->unique()->safeEmail;
        $response = $this->json('POST', '/api/v1/auth/register', $request);
        $response->assertStatus(200);

        $request['username'] = '_9the_user9';
        $request['email'] = $this->faker->unique()->safeEmail;
        $response = $this->json('POST', '/api/v1/auth/register', $request);
        $response->assertStatus(200);
    }
}
