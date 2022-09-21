<?php 

use App\Models;
use Tests\MockData;
use Illuminate\Support\Str;


it('works when user is not signed in', function()
{
        $user_to_get = Models\User::factory()->create();
        $user_to_get->tags()->create([
            'name' => 'Football',
            'tag_priority' => 1,
        ]);

        $response = $this->json('GET', "/api/v1/users/{$user_to_get->id}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateGetUserResponse());
});

it('works when username is passed', function ()
{
        $user_to_get = Models\User::factory()->create();

        $response = $this->json('GET', "/api/v1/users/{$user_to_get->username}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateGetUserResponse());
});

it('works when email is passed', function () {
    $user_to_get = Models\User::factory()->create();

        $response = $this->json('GET', "/api/v1/users/{$user_to_get->email}");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateGetUserResponse());
});

test('followers info is returned correctly', function()
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
});

test('following info is returned correctly', function()
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
});