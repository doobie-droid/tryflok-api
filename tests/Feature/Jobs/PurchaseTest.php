<?php

namespace Tests\Feature\Jobs;

use App\Constants\Constants;
use App\Constants\Roles;
use App\Jobs\Payment\Purchase as PurchaseJob;
use App\Models\Benefactor;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Price;
use App\Models\User;
use App\Models\Userable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_purchase_works()
    {
        $creator = User::factory()->create();
        $creator->assignRole(Roles::USER);
        $buyer = User::factory()->create();
        $buyer->assignRole(Roles::USER);
        $free_digiverse = Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->has(Price::factory()
            ->subscription()
            ->state([
            'amount' => 0,
            ])
            ->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $creator->id
            ])
        )
        ->create();

        $free_content_in_free_digiverse = Content::factory()
        ->for($creator, 'owner')
        ->has(Price::factory()->state([
            'amount' => 0,
        ])->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $creator->id
            ])
        )
        ->create();

        $paid_content_in_free_digiverse = Content::factory()
        ->for($creator, 'owner')
        ->has(Price::factory()->state([
            'amount' => 100,
        ])->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $creator->id
            ])
        )
        ->create();

        $paid_digiverse = Collection::factory()
        ->for($creator, 'owner')
        ->digiverse()
        ->has(Price::factory()->subscription()->state([
            'amount' => 100,
        ])->count(1))
        ->has(
            Benefactor::factory()->state([
                'user_id' => $creator->id
            ])
        )
        ->create();

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
            'platform_share' => bcmul(100, Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(100, 100 - Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'content',
            'revenueable_id' => $paid_content_in_free_digiverse->id,
            'amount' => 100,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(100, Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(100, 100 - Constants::NORMAL_CREATOR_CHARGE, 2),
            'referral_bonus' => 0,
            'revenue_from' => 'sale',
        ]);

        $this->assertDatabaseHas('revenues', [
            'user_id' => $creator->id,
            'revenueable_type' => 'content',
            'revenueable_id' => $free_content_in_free_digiverse->id,
            'amount' => 0,
            'payment_processor_fee' => 0,
            'platform_share' => bcmul(0, Constants::NORMAL_CREATOR_CHARGE, 2),
            'benefactor_share' => bcmul(0, 100 - Constants::NORMAL_CREATOR_CHARGE, 2),
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

        $collectionUserable = Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'collection')->where('userable_id', $paid_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($collectionUserable));
        $this->assertDatabaseHas('subscriptions', [
            'subscriptionable_type' => 'collection',
            'subscriptionable_id' => $paid_digiverse->id,
            'userable_id' => $collectionUserable->id,
            'price_id' => $paid_digiverse->prices()->first()->id,
        ]);

        $contentUserable1 = Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'content')->where('userable_id', $paid_content_in_free_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($contentUserable1));
        $this->assertDatabaseMissing('subscriptions', [
            'subscriptionable_type' => 'content',
            'subscriptionable_id' => $paid_content_in_free_digiverse->id,
            'userable_id' => $contentUserable1->id,
            'price_id' => $paid_content_in_free_digiverse->prices()->first()->id,
        ]);

        $contentUserable2 = Userable::where('user_id', $buyer->id)->whereNull('parent_id')->where('userable_type', 'content')->where('userable_id', $free_content_in_free_digiverse->id)->where('status', 'available')->first();
        $this->assertFalse(is_null($contentUserable2));
        $this->assertDatabaseMissing('subscriptions', [
            'subscriptionable_type' => 'content',
            'subscriptionable_id' => $free_content_in_free_digiverse->id,
            'userable_id' => $contentUserable2->id,
            'price_id' => $free_content_in_free_digiverse->prices()->first()->id,
        ]);
    }
}
