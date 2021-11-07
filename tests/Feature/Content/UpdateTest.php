<?php

namespace Tests\Feature\Content;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Collection;
use App\Models\Content;
use App\Models\Benefactor;
use App\Models\Tag;
use App\Models\Asset;
use App\Models\Price;
use App\Constants\Roles;
use Tests\MockData\Content as ContentMock;

class UpdateTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    private function generateSingleContent($user)
    {
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $content = Content::factory()
        ->state([
            'type' => 'audio',
            'title' => 'title before update',
            'description' => 'description before update',
            'is_available' => 1,
        ])
        ->hasAttached(Asset::factory()->audio()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'content-asset',
        ])
        ->hasAttached(Asset::factory()->count(1),
        [
            'id' => Str::uuid(),
            'purpose' => 'cover',
        ])
        ->hasAttached($tag1, [
            'id' => Str::uuid(),
        ])
        ->hasAttached($tag2, [
            'id' => Str::uuid(),
        ])
        ->hasAttached(
            Collection::factory()->digiverse(),
            [
                'id' => Str::uuid(),
            ]
        )
        ->has(Price::factory()->state([
            'amount' => 10,
            'interval' => 'one-off',
            'interval_amount' => 1,
        ])->count(1))
        ->for($user, 'owner')
        ->create();

        $price = $content->prices()->first();
        $asset = $content->assets()->first();
        $cover = $content->cover()->first();
        
        return [
            'content' => $content,
            'cover' => $cover,
            'asset' => $asset,
            'tags' => [
                $tag1, 
                $tag2,
            ],
            'price' => $price,
        ];
    }

    public function test_content_is_not_updated_with_invalid_input()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $testData = $this->generateSingleContent($user);
        $content = $testData['content'];

        $coverAsset = Asset::factory()->create();
        $pdfAsset = Asset::factory()->pdf()->create();
        $audioAsset = Asset::factory()->audio()->create();
        $tag = Tag::factory()->create();
        $complete_request = [
            'title' => 'A content',
            'description' => 'Content description',
            'type' => 'audio',
            'asset_id' => $audioAsset->id,
            'is_available' => 1,
            'price' => [
                'amount' => 10,
            ],
            'tags' => [
                [
                    'id' => $tag->id,
                    'action' => 'add',
                ]
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        // when wrong content ID is provided 
        $response = $this->json('PATCH', '/api/v1/contents/dfdferero9-2343s-2343', $complete_request);
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'id'
            ]
        ]);
        // when title is too long
        $request = $complete_request;
        $request['title'] = Str::random(201);
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $request);
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'title'
            ]
        ]);

        // when is_available is not valid
        $request = $complete_request;
        $request['is_available'] = 10;
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $request);
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'is_available'
            ]
        ]);

        //when an invalid asset type is passed
        $request = $complete_request;
        $request['asset_id'] = $pdfAsset->id;
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $request);
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'asset_id'
            ]
        ]);

        //when an asset id that does not exist is passed
        $request = $complete_request;
        $request['asset_id'] = 'sdsd-dsd-23dsds';
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $request);
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'asset_id'
            ]
        ]);

        // when wrong asset is passed to cover
        $request = $complete_request;
        $request['cover']['asset_id'] = $pdfAsset->id;
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $request);
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'cover.asset_id'
            ]
        ]);

        // when a cover asset id that does not exist is supplied
        $request = $complete_request;
        $request['cover']['asset_id'] = 'sdsd-dsd-23dsds';
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $request);
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'cover.asset_id'
            ]
        ]);

        // when price is not valid
        $request = $complete_request;
        $request['price']['amount'] = -10;
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $request);
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'price.amount'
            ]
        ]);

        //when tag action is not valid
        $request = $complete_request;
        $request['tags'] = [
            [
                'id' => $tag->id,
                'action' => 'adfdf',
            ]
        ];
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $request);
        $response->assertStatus(400)
        ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'tags.0.action',
            ]
        ]);

         //when tag ID is not valid
         $request = $complete_request;
         $request['tags'] = [
            [
                'id' => 'fdfdf-sdfdfdf-2343',
                'action' => 'add',
            ]
         ];
         $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $request);
         $response->assertStatus(400)
         ->assertJsonStructure([
            'status',
            'message',
            'errors' => [
                'tags.0.id',
            ]
         ]);

        //when user does not own content
        $user2 = User::factory()->create();
        $user2->assignRole(Roles::USER);
        $this->be($user2);
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $complete_request);
        $response->assertStatus(400);
    }

    public function test_content_is_updated_with_valid_inputs()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $test_data = $this->generateSingleContent($user);
        $content = $test_data['content'];
        $old_asset_id = $test_data['asset']->id;
        $old_cover_id = $test_data['cover']->id;
        $old_tag1 = $test_data['tags'][0];
        $old_tag2 = $test_data['tags'][1];

        $cover_asset = Asset::factory()->create();
        $audio_asset = Asset::factory()->audio()->create();
        $tag = Tag::factory()->create();
        // when all inputs are present
        $complete_request = [
            'title' => 'A content',
            'description' => 'Content description',
            'asset_id' => $audio_asset->id,
            'price' => [
                'amount' => 100,
            ],
            'tags' => [
                [
                    'id' => $tag->id,
                    'action' => 'add',
                ],
                [
                    'id' => $old_tag1->id,
                    'action' => 'remove',
                ]
            ],
            'cover' => [
                'asset_id' => $cover_asset->id,
            ],
            'is_available' => 0,
        ];

        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $complete_request);
        $response->assertStatus(200)
        ->assertJsonStructure(ContentMock::STANDARD_CONTENT_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'id' => $content->id,
            'title' => $complete_request['title'],
            'description' => $complete_request['description'],
            'user_id' => $user->id,
            'is_available' => 0,
        ]);

        // validate tags were updated
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag->id)->count() === 1);
        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $old_tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $old_tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $old_tag2->id)->count() === 1);

        // validate cover was changed
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $cover_asset->id,
            'purpose' => 'cover',
        ]);
        $this->assertDatabaseMissing('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $old_cover_id,
        ]);
        $this->assertDatabaseMissing('assets', [
            'id' => $old_cover_id,
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate asset was changed
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $audio_asset->id,
            'purpose' => 'content-asset',
        ]);
        $this->assertDatabaseMissing('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $old_asset_id,
        ]);
        $this->assertDatabaseMissing('assets', [
            'id' => $old_asset_id,
        ]);
        $this->assertTrue($content->assets()->count() === 1);

        //validate price changed
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $complete_request['price']['amount'],
        ]);
        $this->assertTrue($content->prices()->count() === 1);
    }
}
