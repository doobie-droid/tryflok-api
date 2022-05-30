<?php

namespace Tests\Feature\Controllers\API\V1\Auth\AuthController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MockData;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_works_via_email()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::SUPER_ADMIN);
        $user->assignRole(Constants\Roles::ADMIN);
        
        $request = [
            'username' => $user->email,
            'password' => 'password',
        ];
        $response = $this->json('POST', '/api/v1/auth/login', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJson(MockData\User::generateStandardUserResponseJson($user->name, $user->email, $user->username, [Constants\Roles::SUPER_ADMIN, Constants\Roles::ADMIN, Constants\Roles::USER]));
    }

    public function test_login_works_via_username()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::SUPER_ADMIN);
        $user->assignRole(Constants\Roles::ADMIN);

        $request = [
            'username' => $user->username,
            'password' => 'password',
        ];
        $response = $this->json('POST', '/api/v1/auth/login', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJson(MockData\User::generateStandardUserResponseJson($user->name, $user->email, $user->username, [Constants\Roles::SUPER_ADMIN, Constants\Roles::ADMIN, Constants\Roles::USER]));
    }

    public function test_refresh_token_works()
    {
        $user = Models\User::factory()->create();
        $this->be($user);
        $token = JWTAuth::fromUser($user);
        $server = [
            'HTTP_Authorization' => 'Bearer ' . $token
        ];
    
        $response = $this->json('PATCH', '/api/v1/account/token', [], $server);
        $response->assertStatus(200)->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE);
    }
}
