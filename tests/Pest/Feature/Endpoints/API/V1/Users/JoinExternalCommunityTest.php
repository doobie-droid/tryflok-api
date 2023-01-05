<?php

use App\Models;

test('join external community works', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();

    $request = [
        'name' => $user2->name,
        'email' => $user2->email,
    ];

    $response = $this->json('POST', "/api/v1/external-community/{$user->id}/join", $request);
    $response->assertStatus(200);

    $this->assertDatabaseHas('external_communities', [
        'user_id' => $user->id,
        'name' => $user2->name,
        'email' => $user2->email
    ]);
});

it('does not work if email is not supplied', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();

    $request = [
        'name' => $user2->name,
    ];

    $response = $this->json('POST', "/api/v1/external-community/{$user->id}/join", $request);
    $response->assertStatus(400);

    $this->assertDatabaseMissing('external_communities', [
        'user_id' => $user->id,
        'name' => $user2->name,
        'email' => $user2->email
    ]);
});

it('it does not work with invalid user id', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();

    $request = [
        'name' => $user2->name,
        'email' => $user2->email
    ];

    $response = $this->json('POST', "/api/v1/external-community/123/join", $request);
    $response->assertStatus(400);

    $this->assertDatabaseMissing('external_communities', [
        'user_id' => $user->id,
        'name' => $user2->name,
        'email' => $user2->email
    ]);
});

it('works if name is not supplied', function()
{
    $user = Models\User::factory()->create();
    $user2 = Models\User::factory()->create();

    $request = [
        'email' => $user2->email
    ];

    $response = $this->json('POST', "/api/v1/external-community/{$user->id}/join", $request);
    $response->assertStatus(200);

    $this->assertDatabaseHas('external_communities', [
        'user_id' => $user->id,
        'email' => $user2->email
    ]);
});