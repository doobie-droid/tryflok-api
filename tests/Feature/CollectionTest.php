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

class CollectionTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * Test collection edit
     *
     * @return void
     */
    public function test_edit_collection()
    {
        //purchase items for the users
        $users = User::all();
        $creator = User::find(4);
        $collection = Collection::where('public_id', MockCollection::SEEDED_COLLECTION_WITH_SUB['public_id'])->first();
        $sub1 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_1_LEVEL_1['public_id'])->first();
        $sub2 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_2_LEVEL_1['public_id'])->first();
        $sub1Child1 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_1_LEVEL_1_CHILD_1['public_id'])->first();
        $sub1Child2 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_1_LEVEL_1_CHILD_2['public_id'])->first();
        $sub2Child1 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_2_LEVEL_1_CHILD_1['public_id'])->first();
        $sub2Child2 = Collection::where('public_id', MockCollection::SEEDED_SUB_COLLECTION_2_LEVEL_1_CHILD_2['public_id'])->first();
        $videoContent = Content::find(1);
        $imageContent = Content::where('public_id', MockContent::SEEDED_UNPAID_IMAGE_BOOK['public_id'])->first();
        $pdfContent = Content::where('public_id', MockContent::SEEDED_UNPAID_PDF_BOOK['public_id'])->first();
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
                        "public_id" => $pdfContent->public_id,
                        "type" => "content",
                        "price" => [
                            "public_id" => $pdfContent->prices()->first()->public_id,
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
        //edit the collection and remove collection 2 and video content, add image content
        $this->be($creator);
        $response = $this->json('POST', '/api/v1/collections/' . $collection->public_id, [
            'contents' => [
                [
                    'public_id' => $videoContent->public_id,
                    'action' => 'remove',
                ],
                [
                    'public_id' => $imageContent->public_id,
                    'action' => 'add',
                ],
            ],
            'collections' => [
                [
                    'public_id' => $sub2->public_id,
                    'action' => 'remove',
                ],
            ]
        ]);
        
        $response->assertStatus(200);

        //verify video content is not under colelction 1
        $this->assertDatabaseMissing('collection_content', [
            'collection_id' => $collection->id,
            'content_id' => $videoContent->id,
        ]);

        //verify that image content was added to collection 1
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $collection->id,
            'content_id' => $imageContent->id,
        ]);
        //verify that collection 2 is not under the collection again
        $this->assertDatabaseMissing('collection_collection', [
            'parent_id' => $collection->id,
            'child_id' => $sub2->id,
        ]);

        //verify that collection 1 is still under the collection
        $this->assertDatabaseHas('collection_collection', [
            'parent_id' => $collection->id,
            'child_id' => $sub1->id,
        ]);

        //assert that sub2's relations are intact
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $sub2->id,
            'content_id' => 1,
        ]);
        $this->assertDatabaseHas('collection_collection', [
            'parent_id' => $sub2->id,
            'child_id' => $sub2Child1->id,
        ]);
        $this->assertDatabaseHas('collection_collection', [
            'parent_id' => $sub2->id,
            'child_id' => $sub2Child2->id,
        ]);

        $i = 0;
        foreach ($users as $user) {
            $collectionUserable = Userable::where('user_id', $user->id)->whereNull('parent_id')->where('userable_type', 'collection')->where('userable_id', $collection->id)->where('status', 'available')->first();
            $this->assertFalse(is_null($collectionUserable));
            //verify image content is under collection 1 and userables of all the users
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $collectionUserable->id,
                'status' => 'available',
                'userable_type' => 'content',
                'userable_id' => $imageContent->id,
            ]);
            //verify that collection 1 and it's userables remain
            $this->assertDatabaseHas('userables', [
                'user_id' => $user->id,
                'parent_id' => $collectionUserable->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $sub1->id,
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
            //verify that all of collection's two userables are removed
            $this->assertDatabaseMissing('userables', [
                'user_id' => $user->id,
                'parent_id' => $collectionUserable->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $sub2->id,
            ]);
            //check that sub2's children were removed
            $this->assertDatabaseMissing('userables', [
                'user_id' => $user->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $sub2Child1->id,
            ]);
            $this->assertDatabaseMissing('userables', [
                'user_id' => $user->id,
                'status' => 'available',
                'userable_type' => 'collection',
                'userable_id' => $sub2Child2->id,
            ]);

            $i++;
            if ($i > 2) {
                break;
            }
        }
    }
  
}
