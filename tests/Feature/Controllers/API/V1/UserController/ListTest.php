<?php

namespace Tests\Feature\Controllers\API\V1\UserController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class ListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_list_users_fails_with_invalid_parameters()
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
    }

    public function test_list_users_work_when_user_is_not_signed_in()
    {
        Models\User::factory()->count(4)->create();

        $response = $this->json('GET', '/api/v1/users');    
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateListUsersResponse());
        $users = $response->getData()->data->users;
        $this->assertEquals(count($users), 4);
    }

    public function test_filter_by_keyword_works()
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
    }

    public function test_followers_info_is_returned_correctly()
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
        $this->assertEquals($users[0]->followers_count, 1);
        $this->assertEquals(count($users[0]->followers), 1);
        $this->assertEquals($users[0]->followers[0]->id, $user->id);
    }

public function test_following_info_is_returned_correctly()
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
        $this->assertEquals($users[0]->following_count, 1);
        $this->assertEquals(count($users[0]->following), 1);
        $this->assertEquals($users[0]->following[0]->id, $user->id);
    }
}
