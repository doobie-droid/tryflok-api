<?php

namespace Tests\Feature\Controllers\API\V1\UserController;

use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_works_when_user_is_not_signed_in()
    {
        $user_to_get = Models\User::factory()->create();

        $response = $this->json('GET', "/api/v1/users/{$user_to_get->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateGetUserResponse());
    }

    public function test_followers_info_is_returned_correctly()
    {
        $user_to_get = Models\User::factory()->create();
        $user_making_request = Models\User::factory()->create();
        $this->be($user_making_request);
        $auxialliary_user = Models\User::factory()->create();

        $user_to_get->followers()->syncWithoutDetaching([
            $user_making_request->id => [
                'id' => Str::uuid(),
            ],
        ]);

        $user_to_get->followers()->syncWithoutDetaching([
            $auxialliary_user->id => [
                'id' => Str::uuid(),
            ],
        ]);

        $response = $this->json('GET', "/api/v1/users/{$user_to_get->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateGetUserResponse());

        $user = $response->getData()->data->user;
        $this->assertEquals($user->followers_count, 2);
        $this->assertEquals(count($user->followers), 1);
        $this->assertEquals($user->followers[0]->id, $user_making_request->id);
    }

    public function test_following_info_is_returned_correctly()
    {
        $user_to_get = Models\User::factory()->create();
        $user_making_request = Models\User::factory()->create();
        $this->be($user_making_request);
        $auxialliary_user = Models\User::factory()->create();

        $user_making_request->followers()->syncWithoutDetaching([
            $user_to_get->id => [
                'id' => Str::uuid(),
            ],
        ]);

        $auxialliary_user->followers()->syncWithoutDetaching([
            $user_to_get->id => [
                'id' => Str::uuid(),
            ],
        ]);

        $response = $this->json('GET', "/api/v1/users/{$user_to_get->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateGetUserResponse());

        $user = $response->getData()->data->user;
        $this->assertEquals($user->following_count, 2);
        $this->assertEquals(count($user->following), 1);
        $this->assertEquals($user->following[0]->id, $user_making_request->id);
    }
}
