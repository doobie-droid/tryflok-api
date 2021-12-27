<?php

namespace Tests\Feature\Controllers\API\V1\Auth\AuthController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\MockData;

class LoginViaOtpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_via_otp_works()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $user->otps()->create([
            'code' => 'TEST-OTP',
            'purpose' => 'authentication',
            'expires_at' => now()->addMinutes(2),
        ]);
        $request = [
            'code' => 'TEST-OTP',
        ];
        $response = $this->json('POST', '/api/v1/auth/otp-login', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJsonStructure(MockData\User::STANDARD_USER_RESPONSE_STRUCTURE)
        ->assertJson(
            MockData\User::generateStandardUserResponseJson($user->name, $user->email, $user->username, [Constants\Roles::USER])
        );
    }

    public function test_invalid_code_does_not_work()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $request = [
            'code' => 'TEST-OTP',
        ];
        $response = $this->json('POST', '/api/v1/auth/otp-login', $request);
        $response->assertStatus(400);
    }

    public function test_expired_code_does_not_work()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $user->otps()->create([
            'code' => 'TEST-OTP',
            'purpose' => 'authentication',
            'expires_at' => now()->subMinutes(2),
        ]);
        $request = [
            'code' => 'TEST-OTP',
        ];
        $response = $this->json('POST', '/api/v1/auth/otp-login', $request);
        $response->assertStatus(400);
    }
}
