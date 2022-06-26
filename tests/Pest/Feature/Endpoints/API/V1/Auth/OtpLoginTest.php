<?php
use App\Constants;
use App\Models;
use Tests\MockData;

beforeEach(function () {
    $this->user = Models\User::factory()->create();
    $this->be($this->user);
});

test('login via otp works', function(){
        $this->user->otps()->create([
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
            MockData\User::generateStandardUserResponseJson($this->user->name, $this->user->email, $this->user->username, [Constants\Roles::USER])
        );
});
test('invalid code does not work', function(){
    $request = [
        'code' => 'TEST-OTP',
    ];
    $response = $this->json('POST', '/api/v1/auth/otp-login', $request);
    $response->assertStatus(400);
});

test('expired code does not work', function(){
    $this->user->otps()->create([
        'code' => 'TEST-OTP',
        'purpose' => 'authentication',
        'expires_at' => now()->subMinutes(2),
    ]);
    $request = [
        'code' => 'TEST-OTP',
    ];
    $response = $this->json('POST', '/api/v1/auth/otp-login', $request);
    $response->assertStatus(400);
});