<?php

use App\Models;

test('export works', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $externalCommunity = Models\ExternalCommunity::factory()
    ->count(10)
    ->create();

    $response = $this->json('GET', "/api/v1/external-community");
    dd($response->getData());
    $response->assertStatus(200);
});