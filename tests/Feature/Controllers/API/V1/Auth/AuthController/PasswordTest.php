<?php

namespace Tests\Feature\Controllers\API\V1\Auth\AuthController;

use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use DatabaseTransactions;

    // TO DO: forgot password
    // TO DO: reset password
    public function test_update_password_works()
    {
        $user = Models\User::factory()->create();
        $this->be($user);
        $response = $this->json('PUT', '/api/v1/account/password', [
            'old' => 'password',
            'password' => 'user126',
            'password_confirmation' => 'user126',
        ]);
        $response->assertStatus(200);
        $this->assertTrue(Hash::check('user126', $user->password));
    }
}
