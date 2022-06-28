<?php 

use App\Models;
use Tests\MockData;

test('list revenues returns 401 when user is not signed in', function()
{
        $user = Models\User::factory()->create();
        $response = $this->json('GET', '/api/v1/account/revenues');
        $response->assertStatus(401);
});

test('list revenue works', function()
{
        $user = Models\User::factory()->create();
        Models\Revenue::factory()
                ->for($user, 'user')
                ->create();
        $this->be($user);
        $response = $this->json('GET', '/api/v1/account/revenues');
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateListRevenuesResponse());
});