<?php 

use App\Constants;
use App\Jobs\Payment\Purchase as PurchaseJob;
use App\Mail\User\SaleMade;
use App\Models;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

test('purchase works', function()
{
        $creator = Models\User::factory()->create();
        $buyer = Models\User::factory()->create();
        $free_digiverse = Models\Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->create();

        $free_content_in_free_digiverse = Models\Content::factory()
        ->for($creator, 'owner')
        ->create();

        $paid_content_in_free_digiverse = Models\Content::factory()
        ->for($creator, 'owner')
        ->setPriceAmount(100)
        ->create();

        $paid_digiverse = Models\Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->setPriceAmount(100)
        ->create();
        Mail::fake();
        PurchaseJob::dispatch([
            'total_amount' => 200,
            'total_fees' => 0,
            'provider' => 'wallet',
            'provider_id' => Str::uuid(),
            'user' => $buyer->toArray(),
            'items' => [
                [
                    'id' => $paid_digiverse->id,
                    'type' => 'collection',
                    'originating_client_source' => 'web',
                    'price' => [
                        'id' => $paid_digiverse->prices()->first()->id,
                        'amount' => $paid_digiverse->prices()->first()->amount,
                        'interval' => 'monthly',
                        'interval_amount' => 1,
                    ],
                ],
                [
                    'id' => $free_content_in_free_digiverse->id,
                    'type' => 'content',
                    'originating_client_source' => 'web',
                    'price' => [
                        'id' => $free_content_in_free_digiverse->prices()->first()->id,
                        'amount' => 0,
                        'interval' => 'one-off',
                        'interval_amount' => 1,
                    ],
                ],
                [
                    'id' => $paid_content_in_free_digiverse->id,
                    'type' => 'content',
                    'originating_client_source' => 'web',
                    'price' => [
                        'id' => $paid_content_in_free_digiverse->prices()->first()->id,
                        'amount' => $paid_content_in_free_digiverse->prices()->first()->amount,
                        'interval' => 'one-off',
                        'interval_amount' => 1,
                    ],
                ],
            ],
        ]);
        Mail::assertSent(SaleMade::class);

        $this->assertDatabaseHas('payments', [
            'payer_id' => $buyer->id,
            'payee_id' => $creator->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'paymentable_type' => 'collection',
            'paymentable_id' => $paid_digiverse->id,

        ]);
        $this->assertDatabaseHas('payments', [
            'payer_id' => $buyer->id,
            'payee_id' => $creator->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'paymentable_type' => 'content',
            'paymentable_id' => $paid_content_in_free_digiverse->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'payer_id' => $buyer->id,
            'payee_id' => $creator->id,
            'amount' => 0,
            'payment_processor_fee' => 0,
            'paymentable_type' => 'content',
            'paymentable_id' => $free_content_in_free_digiverse->id,
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'collection',
            'revenueable_id' => $paid_digiverse->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(100, Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(100, 1 - Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
            'originating_client_source' => 'web',
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'content',
            'revenueable_id' => $paid_content_in_free_digiverse->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(100, Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(100, 1 - Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
            'originating_client_source' => 'web',
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'content',
            'revenueable_id' => $free_content_in_free_digiverse->id,
            'amount' => 0,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(0, Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(0, 1 - Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
            'originating_client_source' => 'web',
        ]);

        $this->assertDatabaseHas('carts', [
            'cartable_type' => 'content',
            'cartable_id' => $free_content_in_free_digiverse->id,
            'user_id' => $buyer->id,
            'checked_out' => 1,
            'status' => 'completed',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('carts', [
            'cartable_type' => 'content',
            'cartable_id' => $paid_content_in_free_digiverse->id,
            'user_id' => $buyer->id,
            'checked_out' => 1,
            'status' => 'completed',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('carts', [
            'cartable_type' => 'collection',
            'cartable_id' => $paid_digiverse->id,
            'user_id' => $buyer->id,
            'checked_out' => 1,
            'status' => 'completed',
            'quantity' => 1,
        ]);

        $collectionUserable = Models\Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'collection')->where('userable_id', $paid_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($collectionUserable));
        $this->assertDatabaseHas('subscriptions', [
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $paid_digiverse->id,
            'userable_id' => $collectionUserable->id,
            'price_id' => $paid_digiverse->prices()->first()->id,
        ]);

        $contentUserable1 = Models\Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'content')->where('userable_id', $paid_content_in_free_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($contentUserable1));
        $this->assertDatabaseMissing('subscriptions', [
            'subscriptionable_type' => 'content',
            'subscriptionable_id' => $paid_content_in_free_digiverse->id,
            'userable_id' => $contentUserable1->id,
            'price_id' => $paid_content_in_free_digiverse->prices()->first()->id,
        ]);

        $contentUserable2 = Models\Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'content')->where('userable_id', $free_content_in_free_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($contentUserable2));
        $this->assertDatabaseMissing('subscriptions', [
            'subscriptionable_type' => 'content',
            'subscriptionable_id' => $free_content_in_free_digiverse->id,
            'userable_id' => $contentUserable2->id,
            'price_id' => $free_content_in_free_digiverse->prices()->first()->id,
        ]);

})->skip();

test('purchase works without optional parameters', function()
{
        $creator = Models\User::factory()->create();
        $buyer = Models\User::factory()->create();
        $free_digiverse = Models\Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->create();

        $free_content_in_free_digiverse = Models\Content::factory()
        ->for($creator, 'owner')
        ->create();

        $paid_content_in_free_digiverse = Models\Content::factory()
        ->for($creator, 'owner')
        ->setPriceAmount(100)
        ->create();

        $paid_digiverse = Models\Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->setPriceAmount(100)
        ->create();
        Mail::fake();
        PurchaseJob::dispatch([
            'total_amount' => 200,
            'total_fees' => 0,
            'provider' => 'wallet',
            'provider_id' => Str::uuid(),
            'user' => $buyer->toArray(),
            'items' => [
                [
                    'id' => $paid_digiverse->id,
                    'type' => 'collection',
                    'price' => [
                        'id' => $paid_digiverse->prices()->first()->id,
                        'amount' => $paid_digiverse->prices()->first()->amount,
                        'interval' => 'monthly',
                        'interval_amount' => 1,
                    ],
                ],
                [
                    'id' => $free_content_in_free_digiverse->id,
                    'type' => 'content',
                    'price' => [
                        'id' => $free_content_in_free_digiverse->prices()->first()->id,
                        'amount' => 0,
                        'interval' => 'one-off',
                        'interval_amount' => 1,
                    ],
                ],
                [
                    'id' => $paid_content_in_free_digiverse->id,
                    'type' => 'content',
                    'price' => [
                        'id' => $paid_content_in_free_digiverse->prices()->first()->id,
                        'amount' => $paid_content_in_free_digiverse->prices()->first()->amount,
                        'interval' => 'one-off',
                        'interval_amount' => 1,
                    ],
                ],
            ],
        ]);
        Mail::assertSent(SaleMade::class);

        $this->assertDatabaseHas('payments', [
            'payer_id' => $buyer->id,
            'payee_id' => $creator->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'paymentable_type' => 'collection',
            'paymentable_id' => $paid_digiverse->id,

        ]);
        $this->assertDatabaseHas('payments', [
            'payer_id' => $buyer->id,
            'payee_id' => $creator->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'paymentable_type' => 'content',
            'paymentable_id' => $paid_content_in_free_digiverse->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'payer_id' => $buyer->id,
            'payee_id' => $creator->id,
            'amount' => 0,
            'payment_processor_fee' => 0,
            'paymentable_type' => 'content',
            'paymentable_id' => $free_content_in_free_digiverse->id,
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'collection',
            'revenueable_id' => $paid_digiverse->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(100, Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(100, 1 - Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'content',
            'revenueable_id' => $paid_content_in_free_digiverse->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(100, Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(100, 1 - Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'content',
            'revenueable_id' => $free_content_in_free_digiverse->id,
            'amount' => 0,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(0, Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(0, 1 - Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);

        $this->assertDatabaseHas('carts', [
            'cartable_type' => 'content',
            'cartable_id' => $free_content_in_free_digiverse->id,
            'user_id' => $buyer->id,
            'checked_out' => 1,
            'status' => 'completed',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('carts', [
            'cartable_type' => 'content',
            'cartable_id' => $paid_content_in_free_digiverse->id,
            'user_id' => $buyer->id,
            'checked_out' => 1,
            'status' => 'completed',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('carts', [
            'cartable_type' => 'collection',
            'cartable_id' => $paid_digiverse->id,
            'user_id' => $buyer->id,
            'checked_out' => 1,
            'status' => 'completed',
            'quantity' => 1,
        ]);

        $collectionUserable = Models\Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'collection')->where('userable_id', $paid_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($collectionUserable));
        $this->assertDatabaseHas('subscriptions', [
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $paid_digiverse->id,
            'userable_id' => $collectionUserable->id,
            'price_id' => $paid_digiverse->prices()->first()->id,
        ]);

        $contentUserable1 = Models\Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'content')->where('userable_id', $paid_content_in_free_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($contentUserable1));
        $this->assertDatabaseMissing('subscriptions', [
            'subscriptionable_type' => 'content',
            'subscriptionable_id' => $paid_content_in_free_digiverse->id,
            'userable_id' => $contentUserable1->id,
            'price_id' => $paid_content_in_free_digiverse->prices()->first()->id,
        ]);

        $contentUserable2 = Models\Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'content')->where('userable_id', $free_content_in_free_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($contentUserable2));
        $this->assertDatabaseMissing('subscriptions', [
            'subscriptionable_type' => 'content',
            'subscriptionable_id' => $free_content_in_free_digiverse->id,
            'userable_id' => $contentUserable2->id,
            'price_id' => $free_content_in_free_digiverse->prices()->first()->id,
        ]);

});

test('subscription is not created when limit is reached', function()
{
        $creator = Models\User::factory()->create();
        $buyer = Models\User::factory()->create();
        $free_digiverse = Models\Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->create();

        $free_content_in_free_digiverse = Models\Content::factory()
        ->for($creator, 'owner')
        ->create();

        $paid_content_in_free_digiverse = Models\Content::factory()
        ->for($creator, 'owner')
        ->setPriceAmount(100)
        ->create();

        $paid_digiverse = Models\Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->setPriceAmount(100)
        ->create([
            'max_subscribers' => 1,
        ]);
        $paid_digiverse->subscriptions()->create([
            'id' => Str::uuid(),
            'userable_id' => Models\Userable::factory()->create()->id,
            'price_id' => Models\Price::factory()->create()->id,
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => Models\Collection::factory()->create()->id,
            'status' => 'active',
            'auto_renew' => 1,
            'start' => now(),
            'end' => now()->add(1, 'month'),
        ]);
        Mail::fake();
        PurchaseJob::dispatch([
            'total_amount' => 200,
            'total_fees' => 0,
            'provider' => 'wallet',
            'provider_id' => Str::uuid(),
            'user' => $buyer->toArray(),
            'items' => [
                [
                    'id' => $paid_digiverse->id,
                    'type' => 'collection',
                    'originating_client_source' => 'web',
                    'price' => [
                        'id' => $paid_digiverse->prices()->first()->id,
                        'amount' => $paid_digiverse->prices()->first()->amount,
                        'interval' => 'monthly',
                        'interval_amount' => 1,
                    ],
                ],
                [
                    'id' => $free_content_in_free_digiverse->id,
                    'type' => 'content',
                    'originating_client_source' => 'web',
                    'price' => [
                        'id' => $free_content_in_free_digiverse->prices()->first()->id,
                        'amount' => 0,
                        'interval' => 'one-off',
                        'interval_amount' => 1,
                    ],
                ],
                [
                    'id' => $paid_content_in_free_digiverse->id,
                    'type' => 'content',
                    'originating_client_source' => 'web',
                    'price' => [
                        'id' => $paid_content_in_free_digiverse->prices()->first()->id,
                        'amount' => $paid_content_in_free_digiverse->prices()->first()->amount,
                        'interval' => 'one-off',
                        'interval_amount' => 1,
                    ],
                ],
            ],
        ]);
        Mail::assertSent(SaleMade::class);

        $this->assertDatabaseHas('payments', [
            'payer_id' => $buyer->id,
            'payee_id' => $creator->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'paymentable_type' => 'collection',
            'paymentable_id' => $paid_digiverse->id,

        ]);
        $this->assertDatabaseHas('payments', [
            'payer_id' => $buyer->id,
            'payee_id' => $creator->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'paymentable_type' => 'content',
            'paymentable_id' => $paid_content_in_free_digiverse->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'payer_id' => $buyer->id,
            'payee_id' => $creator->id,
            'amount' => 0,
            'payment_processor_fee' => 0,
            'paymentable_type' => 'content',
            'paymentable_id' => $free_content_in_free_digiverse->id,
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'collection',
            'revenueable_id' => $paid_digiverse->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(100, Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(100, 1 - Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
            'originating_client_source' => 'web',
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'content',
            'revenueable_id' => $paid_content_in_free_digiverse->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(100, Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(100, 1 - Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
            'originating_client_source' => 'web',
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'content',
            'revenueable_id' => $free_content_in_free_digiverse->id,
            'amount' => 0,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(0, Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(0, 1 - Constants\Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
            'originating_client_source' => 'web',
        ]);

        $this->assertDatabaseHas('carts', [
            'cartable_type' => 'content',
            'cartable_id' => $free_content_in_free_digiverse->id,
            'user_id' => $buyer->id,
            'checked_out' => 1,
            'status' => 'completed',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('carts', [
            'cartable_type' => 'content',
            'cartable_id' => $paid_content_in_free_digiverse->id,
            'user_id' => $buyer->id,
            'checked_out' => 1,
            'status' => 'completed',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('carts', [
            'cartable_type' => 'collection',
            'cartable_id' => $paid_digiverse->id,
            'user_id' => $buyer->id,
            'checked_out' => 1,
            'status' => 'completed',
            'quantity' => 1,
        ]);

        $collectionUserable = Models\Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'collection')->where('userable_id', $paid_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($collectionUserable));
        $this->assertDatabaseMissing('subscriptions', [
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $paid_digiverse->id,
            'userable_id' => $collectionUserable->id,
            'price_id' => $paid_digiverse->prices()->first()->id,
        ]);

        $contentUserable1 = Models\Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'content')->where('userable_id', $paid_content_in_free_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($contentUserable1));
        $this->assertDatabaseMissing('subscriptions', [
            'subscriptionable_type' => 'content',
            'subscriptionable_id' => $paid_content_in_free_digiverse->id,
            'userable_id' => $contentUserable1->id,
            'price_id' => $paid_content_in_free_digiverse->prices()->first()->id,
        ]);

        $contentUserable2 = Models\Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'content')->where('userable_id', $free_content_in_free_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($contentUserable2));
        $this->assertDatabaseMissing('subscriptions', [
            'subscriptionable_type' => 'content',
            'subscriptionable_id' => $free_content_in_free_digiverse->id,
            'userable_id' => $contentUserable2->id,
            'price_id' => $free_content_in_free_digiverse->prices()->first()->id,
        ]);

})->skip();