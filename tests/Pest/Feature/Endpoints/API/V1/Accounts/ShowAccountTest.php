<?php

use App\Models;
use Tests\MockData;


test('get account returns 401 when user is not signed in', function()
{
        $user = Models\User::factory()->create();
        $response = $this->json('GET', '/api/v1/account');
        $response->assertStatus(401);
});

test('get account works', function()
{
        $user = Models\User::factory()->create();
        Models\Wallet::factory()->for($user, 'walletable')->create();
        $this->be($user);

        $response = $this->json('GET', '/api/v1/account');
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateGetAccountResponse());
});