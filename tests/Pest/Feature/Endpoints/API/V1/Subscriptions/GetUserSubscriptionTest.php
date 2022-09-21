<?php 

use App\Models;

beforeEach(function()
{
        $this->user = Models\User::factory()->create();
        $this->collection = Models\Collection::factory()
        ->digiverse()
        ->create();
        $this->price = $this->collection->prices()->first();
});

test('toggle auto renew works', function()
{
        $userable = Models\Userable::factory()
        ->for($this->user)
        ->state([
            'userable_type' => 'collection',
            'userable_id' => $this->collection->id,
        ])
        ->create();
        $subscription = Models\Subscription::factory()
        ->for($userable)
        ->for($this->collection, 'subscriptionable')
        ->for($this->price)
        ->create();

        $this->be($this->user);

        $response = $this->json('GET', "/api/v1/account/subscriptions");
        $response->assertStatus(200);
});