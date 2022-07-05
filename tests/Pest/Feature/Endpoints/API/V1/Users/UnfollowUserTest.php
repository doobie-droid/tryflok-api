<?php 

use App\Models;
use Tests\MockData;
use Illuminate\Support\Str;


test('unfollow user returns 401 when user is not signed in', function()
{
        $user = Models\User::factory()->create();
        $response = $this->json('PATCH', "/api/v1/users/{$user->id}/unfollow");
        $response->assertStatus(401);
});

test('unfollow user fail when user id is invalid', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $response = $this->json('PATCH', "/api/v1/users/sdsd-sdfdf/unfollow");
        $response->assertStatus(400);
});

test('unfollow user fails when user tries to unfollow themselves', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);
        $response = $this->json('PATCH', "/api/v1/users/{$user->id}/unfollow");
        $response->assertStatus(400);
});

test('unfollow user works', function()
{
    $user_making_request = Models\User::factory()->create();
        $this->be($user_making_request);
        $user_to_be_unfollowed = Models\User::factory()->create();

        $user_to_be_unfollowed->followers()->syncWithoutDetaching([
            $user_making_request->id => [
                'id' => Str::uuid(),
            ],
        ]);

        $this->assertDatabaseHas('followers', [
            'user_id' => $user_to_be_unfollowed->id,
            'follower_id' => $user_making_request->id,
        ]);

        $response = $this->json('PATCH', "/api/v1/users/{$user_to_be_unfollowed->id}/unfollow");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateGetUserResponse());
        $user_responded = $response->getData()->data->user;
        $this->assertEquals(count($user_responded->followers), 0);

        $this->assertDatabaseMissing('followers', [
            'user_id' => $user_to_be_unfollowed->id,
            'follower_id' => $user_making_request->id,
        ]);
});