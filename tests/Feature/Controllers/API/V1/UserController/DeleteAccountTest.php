<?php

namespace Tests\Feature\Controllers\API\V1\UserController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use DatabaseTransactions;

    public function test_delete_account_returns_401_when_user_is_not_signed_in()
    {
        $user = Models\User::factory()->create();
        $response = $this->json('DELETE', '/api/v1/account');
        $response->assertStatus(401);
    }

    public function test_delete_account_works()
    {
        $user = Models\User::factory()->create();
        Models\Wallet::factory()->for($user,'walletable')->create();
        $this->be($user);

        $response = $this->json('DELETE', '/api/v1/account');
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateGetAccountResponse());
        $user_returned = $response->getData()->data->user;
        $this->assertTrue($user_returned->deleted_at != null);
    }
}
