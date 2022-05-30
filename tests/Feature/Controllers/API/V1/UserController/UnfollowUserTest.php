<?php

namespace Tests\Feature\Controllers\API\V1\UserController;

use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class UnfollowUserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unfollow_user_returns_401_when_user_is_not_signed_in()
    {
        $user = Models\User::factory()->create();
        $response = $this->json('PATCH', "/api/v1/users/{$user->id}/unfollow");
        $response->assertStatus(401);
    }

    public function test_unfollow_user_fails_when_user_id_is_invalid()
    {
        $user = Models\User::factory()->create();
        $this->be($user);
        $response = $this->json('PATCH', "/api/v1/users/sdsd-sdfdf/unfollow");
        $response->assertStatus(400);
    }

    public function test_unfollow_user_fails_when_user_tries_to_unfollow_themselves()
    {
        $user = Models\User::factory()->create();
        $this->be($user);
        $response = $this->json('PATCH', "/api/v1/users/{$user->id}/unfollow");
        $response->assertStatus(400);
    }

    public function test_unfollow_user_works()
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
    }
}
