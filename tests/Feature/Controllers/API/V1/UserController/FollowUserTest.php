<?php

namespace Tests\Feature\Controllers\API\V1\UserController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class FollowUserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_follow_user_returns_401_when_user_is_not_signed_in()
    {
        $user = Models\User::factory()->create();
        $response = $this->json('PATCH', "/api/v1/users/{$user->id}/follow");
        $response->assertStatus(401);
    }

    public function test_follow_user_fails_when_user_id_is_invalid()
    {
        $user = Models\User::factory()->create();
        $this->be($user);
        $response = $this->json('PATCH', "/api/v1/users/sdsd-sdfdf/follow");
        $response->assertStatus(400);
    }

    public function test_follow_user_fails_when_user_tries_to_follow_themselves()
    {
        $user = Models\User::factory()->create();
        $this->be($user);
        $response = $this->json('PATCH', "/api/v1/users/{$user->id}/follow");
        $response->assertStatus(400);
    }

    public function test_follow_user_works()
    {
        $user_making_request = Models\User::factory()->create();
        $this->be($user_making_request);
        $user_to_be_followed = Models\User::factory()->create();

        $response = $this->json('PATCH', "/api/v1/users/{$user_to_be_followed->id}/follow");
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
            'notifier_id' => $user_making_request->id,
            'notificable_type' => 'user',
            'notificable_id' => $user_making_request->id,
            'message' => "@{$user_making_request->username} followed you",
        ]);
    }
}
