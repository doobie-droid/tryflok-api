<?php 

use App\Models;

test('end_subscription_works', function()
{
    $collection_price = 100;
        $collection = Models\Collection::factory()
        ->setPriceAmount($collection_price)
        ->digiverse()
        ->create();
        $price = $collection->prices()->first();
        $price_in_flk = bcmul($collection_price, 100, 2);

        // subscription has not ended
        $user1 = Models\User::factory()->create();
        $user1_wallet = Models\Wallet::factory()
        ->for($user1, 'walletable')
        ->create();
        $user1_initial_wallet_balance = $user1_wallet->balance;
        $user1_userable = Models\Userable::factory()
        ->for($user1)
        ->state([
            'userable_type' => 'collection',
            'userable_id' => $collection->id,
        ])
        ->create();
        $user1_subscription = Models\Subscription::factory()
        ->for($user1_userable)
        ->for($collection, 'subscriptionable')
        ->for($price)
        ->create();

        // subscription has ended, is set to auto-renew and user has money
        $user2 = Models\User::factory()->create();
        $user2_wallet = Models\Wallet::factory()
        ->for($user2, 'walletable')
        ->create();
        $user2_initial_wallet_balance = $user2_wallet->balance;
        $user2_userable = Models\Userable::factory()
        ->for($user2)
        ->state([
            'userable_type' => 'collection',
            'userable_id' => $collection->id,
        ])
        ->create();
        $user2_subscription = Models\Subscription::factory()
        ->for($user2_userable)
        ->for($price)
        ->for($collection, 'subscriptionable')
        ->state([
            'start' => now()->sub(1, 'month'),
            'end' => now()->sub(1, 'day'),
        ])
        ->create();

        // subscription has ended, is set to auto-renew and user does not have money
        $user3 = Models\User::factory()->create();
        $user3_wallet = Models\Wallet::factory()
        ->for($user3, 'walletable')
        ->state([
            'balance' => 0,
        ])
        ->create();
        $user3_initial_wallet_balance = $user3_wallet->balance;
        $user3_userable = Models\Userable::factory()
        ->for($user3)
        ->state([
            'userable_type' => 'collection',
            'userable_id' => $collection->id,
        ])
        ->create();
        $user3_subscription = Models\Subscription::factory()
        ->for($user3_userable)
        ->for($price)
        ->for($collection, 'subscriptionable')
        ->state([
            'start' => now()->sub(1, 'month'),
            'end' => now()->sub(1, 'day'),
        ])
        ->create();

        // subscription has ended, is not set to auto-renew and user has money
        $user4 = Models\User::factory()->create();
        $user4_wallet = Models\Wallet::factory()
        ->for($user4, 'walletable')
        ->create();
        $user4_initial_wallet_balance = $user4_wallet->balance;
        $user4_userable = Models\Userable::factory()
        ->for($user4)
        ->state([
            'userable_type' => 'collection',
            'userable_id' => $collection->id,
        ])
        ->create();
        $user4_subscription = Models\Subscription::factory()
        ->doNotAutoRenew()
        ->for($user4_userable)
        ->for($price)
        ->for($collection, 'subscriptionable')
        ->state([
            'start' => now()->sub(1, 'month'),
            'end' => now()->sub(1, 'day'),
        ])
        ->create();

        $this->artisan('flok:end-subscriptions')->assertSuccessful();

        // user 1
        $this->assertDatabaseHas('userables', [
            'user_id' => $user1->id,
            'status' => 'available',
            'userable_type' => 'collection',
            'userable_id' => $collection->id,
        ]);
        $this->assertDatabaseMissing('subscriptions', [
            'userable_id' => $user1_userable->id,
            'price_id' => $price->id,
            'status' => 'disabled',
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $collection->id,
        ]);

        $this->assertEquals($user1_wallet->refresh()->balance, $user1_initial_wallet_balance);
        $this->assertDatabaseMissing('wallet_transactions', [
            'wallet_id' => $user1_wallet->id,
            'amount' => $price_in_flk,
            'transaction_type' => 'deduct',
        ]);

        // user 2
        $this->assertDatabaseHas('userables', [
            'user_id' => $user2->id,
            'status' => 'available',
            'userable_type' => 'collection',
            'userable_id' => $collection->id,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'userable_id' => $user2_userable->id,
            'price_id' => $price->id,
            'status' => 'disabled',
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $collection->id,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'userable_id' => $user2_userable->id,
            'price_id' => $price->id,
            'status' => 'active',
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $collection->id,
        ]);

        $this->assertEquals($user2_wallet->refresh()->balance, bcsub($user2_initial_wallet_balance, $price_in_flk, 2));
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $user2_wallet->id,
            'amount' => $price_in_flk,
            'transaction_type' => 'deduct',
        ]);

        // user 3
        $this->assertDatabaseHas('userables', [
            'user_id' => $user3->id,
            'status' => 'subscription-ended',
            'userable_type' => 'collection',
            'userable_id' => $collection->id,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'userable_id' => $user3_userable->id,
            'price_id' => $price->id,
            'status' => 'disabled',
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $collection->id,
        ]);

        $this->assertDatabaseMissing('subscriptions', [
            'userable_id' => $user3_userable->id,
            'price_id' => $price->id,
            'status' => 'active',
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $collection->id,
        ]);

        $this->assertEquals($user3_wallet->refresh()->balance, $user3_initial_wallet_balance);
        $this->assertDatabaseMissing('wallet_transactions', [
            'wallet_id' => $user3_wallet->id,
            'amount' => $price_in_flk,
            'transaction_type' => 'deduct',
        ]);

        // user 4
        $this->assertDatabaseHas('userables', [
            'user_id' => $user4->id,
            'status' => 'subscription-ended',
            'userable_type' => 'collection',
            'userable_id' => $collection->id,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'userable_id' => $user4_userable->id,
            'price_id' => $price->id,
            'status' => 'disabled',
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $collection->id,
        ]);

        $this->assertDatabaseMissing('subscriptions', [
            'userable_id' => $user4_userable->id,
            'price_id' => $price->id,
            'status' => 'active',
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $collection->id,
        ]);

        $this->assertEquals($user4_wallet->refresh()->balance, $user4_initial_wallet_balance);
        $this->assertDatabaseMissing('wallet_transactions', [
            'wallet_id' => $user4_wallet->id,
            'amount' => $price_in_flk,
            'transaction_type' => 'deduct',
        ]);
});