<?php

use App\Models;

test('list external community works', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $externalCommunity = Models\ExternalCommunity::factory()
    ->count(10)
    ->create([
        'user_id' => $user->id,
    ]);
    $response = $this->json('GET', "/api/v1/external-community");
    $response->assertStatus(200);    
});

it('does not work if user is not signed in', function()
{
    $user = Models\User::factory()->create();
    $externalCommunity = Models\ExternalCommunity::factory()
    ->count(10)
    ->create([
        'user_id' => $user->id,
    ]);
    $response = $this->json('GET', "/api/v1/external-community");
    $response->assertStatus(401);   
});