<?php

namespace Tests\Feature\Controllers\API\V1\UserController;

use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\MockData;
use Tests\TestCase;

class GetRevenuesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_list_revenues_returns_401_when_user_is_not_signed_in()
    {
        $user = Models\User::factory()->create();
        $response = $this->json('GET', '/api/v1/account/revenues');
        $response->assertStatus(401);
    }

    public function test_list_revenue_works()
    {
        $user = Models\User::factory()->create();
        Models\Revenue::factory()
                ->for($user, 'user')
                ->create();
        $this->be($user);
        $response = $this->json('GET', '/api/v1/account/revenues');
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\User::generateListRevenuesResponse());
    }
}
