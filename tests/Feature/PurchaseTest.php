<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Userable;
use App\Models\Content;
use App\Models\Collection;
use App\Jobs\Payment\Purchase as PurchaseJob;
use Tests\MockData\Content as MockContent;
use Tests\MockData\Collection as MockCollection;

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
        $users = User::all();
        $creator = User::find(4);
        $collection = Collection::where('public_id', MockCollection::SEEDED_COLLECTION_WITH_SUB['public_id'])->first();
        $content = Content::where('public_id', MockContent::SEEDED_UNPAID_IMAGE_BOOK['public_id'])->first();
        $i = 0;
        foreach ($users as $user) {
            PurchaseJob::dispatch([
                'total_amount' => '4000',
                'total_fees' => '40',
                'provider' => 'local',
                'provider_id' => 'test',
                'user' => $user->toArray(),
                'items' => [
                    [
                        "public_id" => $collection->public_id,
                        "type" => "collection",
                        "price" => [
                            "public_id" => $collection->prices()->first()->public_id,
                            "amount" => "2000.000",
                            "interval" => "month",
                            "interval_amount" => "1",
                        ],
                    ],
                    [
                        "public_id" => $content->public_id,
                        "type" => "content",
                        "price" => [
                            "public_id" => $content->prices()->first()->public_id,
                            "amount" => "2000.000",
                            "interval" => "one-off",
                            "interval_amount" => "0",
                        ],
                    ],
                ],
            ]);

            $i++;
            if ($i > 2) {
                break;
            }
        }

        $i = 0;
        foreach ($users as $user) {
            //check a payment exist for content and collection
            $this->assertDatabaseHas('payments', [
                'payer_id' => $user->id,
                'payee_id' => $creator->id,
                'amount' => '2000',
                'payment_processor_fee' => '20',
                'paymentable_type' => 'collection',
                'paymentable_id' => $collection->id,
            ]);
            $this->assertDatabaseHas('payments', [
                'payer_id' => $user->id,
                'payee_id' => $creator->id,
                'amount' => '2000',
                'payment_processor_fee' => '20',
                'paymentable_type' => 'content',
                'paymentable_id' => $content->id,
            ]);
            //check that sales for benefactors of this prodict was logged
            $net_amount = bcsub(2000, 20, 6);
            $platform_share = bcdiv(bcmul($net_amount, 30,6),100,6);
            $creator_share = bcdiv(bcmul($net_amount, 70,6),100,6);
            foreach ($collection->benefactors as $benefactor) {
                $this->assertDatabaseHas('sales', [
                    'saleable_type' => 'collection',
                    'saleable_id' => $collection->id,
                    'amount' => 2000,
                    'payment_processor_fee' => 20,
                    'platform_share' => $platform_share,
                    'benefactor_share' => bcdiv(bcmul($creator_share, $benefactor->share,6),100,6),
                    'referral_bonus' => 0,
                ]);
            }
            foreach ($content->benefactors as $benefactor) {
                $this->assertDatabaseHas('sales', [
                    'saleable_type' => 'content',
                    'saleable_id' => $content->id,
                    'amount' => 2000,
                    'payment_processor_fee' => 20,
                    'platform_share' => $platform_share,
                    'benefactor_share' => bcdiv(bcmul($creator_share, $benefactor->share,6),100,6),
                    'referral_bonus' => 0,
                ]);
            }
            //check that the referrer was logged if referrer present
            if ($collection->owner->referrer()->exists()) {
                $this->assertDatabaseHas('sales', [
                    'saleable_type' => 'collection',
                    'saleable_id' => $collection->id,
                    'amount' => 2000,
                    'payment_processor_fee' => 20,
                    'platform_share' => $platform_share,
                    'benefactor_share' => 0,
                    'referral_bonus' => bcdiv(bcmul($platform_share, 2.5,6),100,6),
                ]);

                $this->assertDatabaseHas('sales', [
                    'saleable_type' => 'content',
                    'saleable_id' => $content->id,
                    'amount' => 2000,
                    'payment_processor_fee' => 20,
                    'platform_share' => $platform_share,
                    'benefactor_share' => 0,
                    'referral_bonus' => bcdiv(bcmul($platform_share, 2.5,6),100,6),
                ]);
            }

            //if it is a subscription payment check that the subcription was created (this also checks item was logged in userable)
            $collectionUserable = Userable::where('user_id', $user->id)->whereNull('parent_id')->where('userable_type', 'collection')->where('userable_id', $collection->id)->where('status', 'available')->first();
            $this->assertFalse(is_null($collectionUserable));
            $this->assertDatabaseHas('subscriptions', [
                'subscriptionable_type' => 'collection',
                'subscriptionable_id' => $collection->id,
                'userable_id' => $collectionUserable->id,
                'price_id' => $collection->prices()->first()->id,
            ]);
            $contentUserable = Userable::where('user_id', $user->id)->whereNull('parent_id')->where('userable_type', 'content')->where('userable_id', $content->id)->where('status', 'available')->first();
            $this->assertFalse(is_null($contentUserable));
            $this->assertDatabaseMissing('subscriptions', [
                'subscriptionable_type' => 'content',
                'subscriptionable_id' => $content->id,
                'userable_id' => $contentUserable->id,
                'price_id' => $content->prices()->first()->id,
            ]);
            //check that children were userables
            $sub1 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_1_LEVEL_1['public_id'])->first();
            $sub2 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_2_LEVEL_1['public_id'])->first();
            $sub1Child1 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_1_LEVEL_1_CHILD_1['public_id'])->first();
            $sub1Child2 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_1_LEVEL_1_CHILD_2['public_id'])->first();
            $sub2Child1 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_2_LEVEL_1_CHILD_1['public_id'])->first();
            $sub2Child2 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_2_LEVEL_1_CHILD_2['public_id'])->first();
            //ensure children for parent collection are in userables
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $collectionUserable->id,
                'status' => 'available',
                'userable_type' => 'content',
                'userable_id' => 1,
            ]);
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $collectionUserable->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $sub1->id,
            ]);
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $collectionUserable->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $sub2->id,
            ]);
            //check that sub1's children were logged
            $sub1Userable = Userable::where('user_id', $user->id)->where('parent_id', $collectionUserable->id)->where('userable_type', 'collection')->where('userable_id', $sub1->id)->where('status', 'available')->first();
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $sub1Userable->id,
                'status' => 'available',
                'userable_type' => 'content',
                'userable_id' => 1,
            ]);
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $sub1Userable->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $sub1Child1->id,
            ]);
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $sub1Userable->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $sub1Child2->id,
            ]);
            //check that the content for sub1 child 1 was logged in userables
            $sub1Child1Userable = Userable::where('user_id', $user->id)->where('parent_id', $sub1Userable->id)->where('userable_type', 'collection')->where('userable_id', $sub1Child1->id)->where('status', 'available')->first();
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $sub1Child1Userable->id,
                'status' => 'available',
                'userable_type' => 'content',
                'userable_id' => 1,
            ]);

            //check that the content for sub1 child 2 was logged in userables
            $sub1Child2Userable = Userable::where('user_id', $user->id)->where('parent_id', $sub1Userable->id)->where('userable_type', 'collection')->where('userable_id', $sub1Child2->id)->where('status', 'available')->first();
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $sub1Child2Userable->id,
                'status' => 'available',
                'userable_type' => 'content',
                'userable_id' => 1,
            ]);

            //check that sub2's children were logged
            $sub2Userable = Userable::where('user_id', $user->id)->where('parent_id', $collectionUserable->id)->where('userable_type', 'collection')->where('userable_id', $sub2->id)->where('status', 'available')->first();
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $sub2Userable->id,
                'status' => 'available',
                'userable_type' => 'content',
                'userable_id' => 1,
            ]);
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $sub2Userable->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $sub2Child1->id,
            ]);
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $sub2Userable->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $sub2Child2->id,
            ]);
             //check that the content for sub2 child 1 was logged in userables
             $sub2Child1Userable = Userable::where('user_id', $user->id)->where('parent_id', $sub2Userable->id)->where('userable_type', 'collection')->where('userable_id', $sub2Child1->id)->where('status', 'available')->first();
             $this->assertDatabaseHas('userables', [
                 'user_id' => $user->id,
                 'parent_id' => $sub2Child1Userable->id,
                 'status' => 'available',
                 'userable_type' => 'content',
                 'userable_id' => 1,
             ]);
 
             //check that the content for sub1 child 2 was logged in userables
             $sub2Child2Userable = Userable::where('user_id', $user->id)->where('parent_id', $sub2Userable->id)->where('userable_type', 'collection')->where('userable_id', $sub2Child2->id)->where('status', 'available')->first();
             $this->assertDatabaseHas('userables', [
                 'user_id' => $user->id,
                 'parent_id' => $sub2Child2Userable->id,
                 'status' => 'available',
                 'userable_type' => 'content',
                 'userable_id' => 1,
             ]);

            $i++;
            if ($i > 2) {
                break;
            }
        }
    }
}
