<?php

namespace Tests\Feature\Controllers\API\V1\SubscriptionController;

use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ToggleAutoRenewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_toggle_auto_renew_does_not_work_for_invalid_data()
    {
        $user = Models\User::factory()->create();
        $collection = Models\Collection::factory()
        ->digiverse()
        ->create();
        $price = $collection->prices()->first();
        $subscription = Models\Subscription::factory()->for($price)->create();
        $this->be($user);
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
    }

    public function test_toggle_auto_renew_works()
    {
        $collection = Models\Collection::factory()
        ->digiverse()
        ->create();
        $price = $collection->prices()->first();

        $user = Models\User::factory()->create();
        $userable = Models\Userable::factory()
        ->for($user)
        ->state([
            'userable_type' => 'collection',
            'userable_id' => $collection->id,
        ])
        ->create();
        $subscription = Models\Subscription::factory()
        ->for($userable)
        ->for($collection, 'subscriptionable')
        ->for($price)
        ->create();

        $this->be($user);

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
    }
}
