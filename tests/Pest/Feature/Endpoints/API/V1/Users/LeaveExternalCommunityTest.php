<?php

use App\Models;

test('leave external community works', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();

    $externalUser = $user->externalCommunities()->create([
        'name' => $user2->name,
        'email' => $user2->email,
    ]);

    $request = [
        'email' => $user2->email
    ];

    $response = $this->json('PATCH', "/api/v1/external-community/{$user->id}/leave", $request);
    $response->assertStatus(200);

    $this->assertDatabaseMissing('external_communities', [
        'user_id' => $user->id,
        'name' => $user2->name,
        'email' => $user2->email,
    ]);

});

it('does not work if emails do not match', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();
    $user3 = Models\User::factory()->create();

    $externalUser = $user->externalCommunities()->create([
        'name' => $user2->name,
        'email' => $user2->email,
    ]);

    $request = [
        'email' => $user3->email
    ];

    $response = $this->json('PATCH', "/api/v1/external-community/{$user->id}/leave", $request);
    $response->assertStatus(400);

    $this->assertDatabaseHas('external_communities', [
        'user_id' => $user->id,
        'name' => $user2->name,
        'email' => $user2->email,
    ]);
});