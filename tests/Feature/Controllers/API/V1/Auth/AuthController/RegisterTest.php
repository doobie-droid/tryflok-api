<?php

namespace Tests\Feature\Controllers\API\V1\Auth\AuthController;

use App\Constants;
use App\Models;
use App\Notifications\User\EmailConfirmation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\MockData;

class RegisterTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;
    // TO DO: test invalid values are not registered

    public function test_registration_without_referrer_works()
    {
        Notification::fake();
        $request = MockData\User::REGISTRATION_REQUEST;
        $response = $this->json('POST', '/api/v1/auth/register', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJson(
            MockData\User::generateStandardUserResponseJson($request['name'], $request['email'], $request['username'], [Constants\Roles::USER])
        );
        $user = Models\User::where('email', $request['email'])->first();
        Notification::assertSentTo(
            [$user],
            EmailConfirmation::class
        );

        $this->assertDatabaseHas('users', [
            'email' => $request['email'],
            'name' => $request['name'],
            'phone_number' => $request['phone_number'],
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
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJson(MockData\User::generateStandardUserResponseJson($request['name'], $request['email'], $request['username'], [Constants\Roles::USER]));
        $user2 = Models\User::where('email', $request['email'])->first();
        Notification::assertSentTo(
            [$user2],
            EmailConfirmation::class
        );

        $this->assertDatabaseHas('users', [
            'email' => $request['email'],
            'name' => $request,
            'referrer_id' => $user->id,
            'phone_number' => $request['phone_number'],
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

    public function test_verify_email_works()
    {
        $email_token = Str::random(16) . 'YmdHis';
        $user = Models\User::factory()
        ->state([
            'email_verified' => 0,
            'email_token' => $email_token,
        ])
        ->create();

        $request = [
            'token' => $email_token,
        ];
        $response = $this->json('PATCH', '/api/v1/auth/email', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJson(
            MockData\User::generateStandardUserResponseJson($user->name, $user->email, $user->username, [Constants\Roles::USER])
        );

        $this->assertDatabaseHas('users', [
            'email' => $user->email,
            'email_token' => '',
            'email_verified' => 1,
        ]);
    }
}
