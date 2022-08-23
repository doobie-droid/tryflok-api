<?php

use App\Models;
use Tests\MockData;

test('follow user returns 401 when user is not signed in', function()
{
        $user = Models\User::factory()->create();
        $response = $this->json('PATCH', "/api/v1/users/{$user->id}/follow");
        $response->assertStatus(401);
});

test('follow user fails when user id is invalid', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $response = $this->json('PATCH', "/api/v1/users/sdsd-sdfdf/follow");
        $response->assertStatus(400);
});

test('follow user fails when user tries to follow themselves', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $response = $this->json('PATCH', "/api/v1/users/{$user->id}/follow");
        $response->assertStatus(400);
});

test('follow user works', function()
{
    $user_making_request = Models\User::factory()->create();
        $this->be($user_making_request);
        $user_to_be_followed = Models\User::factory()->create();

        $response = $this->json('PATCH', "/api/v1/users/{$user_to_be_followed->id}/follow");
        dd($response);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateGetUserResponse());
        $user_responded = $response->getData()->data->user;
        $this->assertEquals(count($user_responded->followers), 1);
        $this->assertEquals($user_responded->followers[0]->id, $user_making_request->id);

        $this->assertDatabaseHas('followers', [
            'user_id' => $user_to_be_followed->id,
            'follower_id' => $user_making_request->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $user_to_be_followed->id,
            'notifier_id' => $user_making_request->id,
            'notificable_type' => 'user',
            'notificable_id' => $user_making_request->id,
            'message' => "@{$user_making_request->username} followed you",
        ]);
})->only();