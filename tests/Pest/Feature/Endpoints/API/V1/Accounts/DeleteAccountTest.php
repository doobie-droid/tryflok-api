<?php 

use App\Models;
use Tests\MockData;

it('returns 401 when user is not signed in', function()
{
        $user = Models\User::factory()->create();
        $response = $this->json('DELETE', '/api/v1/account');
        $response->assertStatus(401);
});

test('delete account works', function()
{
        $user = Models\User::factory()->create();
        Models\Wallet::factory()->for($user, 'walletable')->create();
        $this->be($user);

        $response = $this->json('DELETE', '/api/v1/account');
        $response->assertStatus(200);
        //->assertJsonStructure(MockData\User::generateGetAccountResponse());
        $user_returned = $response->getData()->data->user;
        $this->assertTrue($user_returned->deleted_at != null);
});