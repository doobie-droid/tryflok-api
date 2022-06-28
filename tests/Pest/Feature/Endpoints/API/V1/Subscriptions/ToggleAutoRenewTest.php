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

test('toggle auto renew does not work for invalid data', function()
{
            $subscription = Models\Subscription::factory()->for($this->price)->create();
            $this->be($this->user);
            // when subscription does not exist
            $response = $this->json('PATCH', '/api/v1/subscriptions/d14b5781-465c-4eca-add3-03d20dd61051', [
                'auto_renew' => 0,
            ]);
            $response->assertStatus(400);
            // when auto_renew value is invalid
            $response = $this->json('PATCH', "/api/v1/subscriptions/{$subscription->id}", [
                'auto_renew' => 500,
            ]);
            $response->assertStatus(400);
            $response = $this->json('PATCH', "/api/v1/subscriptions/{$subscription->id}", [
                'auto_renew' => 'dfdfd',
            ]);
            $response->assertStatus(400);
            // when user is not owner of subscription
            $response = $this->json('PATCH', "/api/v1/subscriptions/{$subscription->id}", [
                'auto_renew' => 1,
            ]);
            $response->assertStatus(400);
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

        $response = $this->json('PATCH', "/api/v1/subscriptions/{$subscription->id}", [
            'auto_renew' => 1,
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'userable_id' => $userable->id,
            'auto_renew' => 1,
        ]);

        $response = $this->json('PATCH', "/api/v1/subscriptions/{$subscription->id}", [
            'auto_renew' => 0,
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'userable_id' => $userable->id,
            'auto_renew' => 0,
        ]);
});