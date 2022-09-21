<?php 

use App\Constants;
use App\Models;
use Illuminate\Support\Str;

use Tests\MockData;

it('fails with invalid parameters', function()
{
    $response = $this->json('GET', '/api/v1/users?page=ere');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);
        $response = $this->json('GET', '/api/v1/users?page=-10');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'page'
            ]
        ]);

        $response = $this->json('GET', '/api/v1/users?limit=ere');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $response = $this->json('GET', '/api/v1/users?limit=-30');
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);
        $max_limit_exceed = Constants\Constants::MAX_ITEMS_LIMIT + 1;
        $response = $this->json('GET', "/api/v1/users?limit={$max_limit_exceed}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'limit'
            ]
        ]);

        $keyword_excess = Str::random(201);
        $response = $this->json('GET', "/api/v1/users?keyword={$keyword_excess}");
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'keyword'
            ]
        ]);
});

it('works when user is not signed in', function()
{
    Models\User::factory()->count(4)->create();

    $response = $this->json('GET', '/api/v1/users');
    $response->assertStatus(200)
    ->assertJsonStructure(MockData\User::generateListUsersResponse());
    $users = $response->getData()->data->users;
});

test('filter by keyword works', function()
{
    $user1 = Models\User::factory()
                    ->state([
                        'name' => 'ssds sdsuser1dsd',
                    ])
                    ->create();

        $user2 = Models\User::factory()
                    ->state([
                        'name' => 'asas dfuser2dfdf',
                    ])
                    ->create();

        $user3 = Models\User::factory()
                    ->state([
                        'username' => 'user3',
                    ])
                    ->create();

        $response = $this->json('GET', '/api/v1/users?keyword=user1 user3');
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateListUsersResponse());
        $users = $response->getData()->data->users;
        $this->assertEquals(count($users), 2);
        $this->assertArrayHasObjectWithElementValue($users, $user1, 'id');
        $this->assertArrayHasObjectWithElementValue($users, $user3, 'id');

        $response = $this->json('GET', '/api/v1/users?keyword=user2 user3');
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateListUsersResponse());
        $users = $response->getData()->data->users;
        $this->assertEquals(count($users), 2);
        $this->assertArrayHasObjectWithElementValue($users, $user2, 'id');
        $this->assertArrayHasObjectWithElementValue($users, $user3, 'id');
});

test('followers info is returned correctly', function()
{
        $user = Models\User::factory()->create();

        $users = Models\User::factory()->count(11)->create();

        foreach ($users as $userInstance) {
            $userInstance->followers()->syncWithoutDetaching([
                $user->id => [
                    'id' => Str::uuid(),
                ],
            ]);
        }

        $this->be($user);
        $response = $this->json('GET', '/api/v1/users');
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateListUsersResponse());
        $users = $response->getData()->data->users;
        $this->assertEquals(count($users), 10);
        $this->assertEquals($users[2]->followers_count, 1);
        $this->assertEquals(count($users[2]->followers), 1);
        $this->assertEquals($users[2]->followers[0]->id, $user->id);
})->skip('Flaky followers test');

test('following info is returned correctly', function()
{
    $user = Models\User::factory()->create();

        $users = Models\User::factory()->count(11)->create();

        foreach ($users as $userInstance) {
            $user->followers()->syncWithoutDetaching([
                $userInstance->id => [
                    'id' => Str::uuid(),
                ],
            ]);
        }

        $this->be($user);
        $response = $this->json('GET', '/api/v1/users');
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateListUsersResponse());
        $users = $response->getData()->data->users;
        $this->assertEquals(count($users), 10);
        $this->assertEquals($users[2]->following_count, 1);
        $this->assertEquals(count($users[2]->following), 1);
        $this->assertEquals($users[2]->following[0]->id, $user->id);
})->skip('Flaky following test');      