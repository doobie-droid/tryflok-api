<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Collection;
use App\Models\Price;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Userable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ToggleAutoRenewTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function test_toggle_auto_renew_does_not_work_for_invalid_data()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()
        ->digiverse()
        ->create();
        $price = Price::factory()
        ->for($collection, 'priceable')
        ->subscription()
        ->create();
        $subscription = Subscription::factory()->for($price)->create();
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
        $collection = Collection::factory()
        ->digiverse()
        ->create();
        $price = Price::factory()
        ->for($collection, 'priceable')
        ->subscription()
        ->create();

        $user = User::factory()->create();
        $userable = Userable::factory()
        ->for($user)
        ->state([
            'userable_type' => 'collection',
            'userable_id' => $collection->id,
        ])
        ->create();
        $subscription = Subscription::factory()
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
