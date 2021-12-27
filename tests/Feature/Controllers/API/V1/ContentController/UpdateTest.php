<?php

namespace Tests\Feature\Controllers\API\V1\ContentController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    public function test_content_is_not_updated_with_invalid_input()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);

        $content = Models\Content::factory()
                    ->for($user, 'owner')
                    ->audio()->create();

        $coverAsset = Models\Asset::factory()->create();
        $pdfAsset = Models\Asset::factory()->pdf()->create();
        $audioAsset = Models\Asset::factory()->audio()->create();
        $tag = Models\Tag::factory()->create();
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
        $user2 = Models\User::factory()->create();
        $user2->assignRole(Constants\Roles::USER);
        $this->be($user2);
        $response = $this->json('PATCH', "/api/v1/contents/{$content->id}", $complete_request);
        $response->assertStatus(400);
    }

    public function test_content_is_updated_with_valid_inputs()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        
        $old_tag1 = Models\Tag::factory()->create();
        $old_tag2 = Models\Tag::factory()->create();
        $content = Models\Content::factory()
            ->for($user, 'owner')
            ->audio()
            ->setTags([$old_tag1, $old_tag2])
            ->create();
        $old_asset_id = $content->assets()->first()->id;
        $old_cover_id = $content->cover()->first()->id;

        $cover_asset = Models\Asset::factory()->create();
        $audio_asset = Models\Asset::factory()->audio()->create();
        $tag = Models\Tag::factory()->create();
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
        ->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

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

    public function test_adding_views_works()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $content = Models\Content::factory()
            ->audio()
            ->setTags([$tag1, $tag2])
            ->create();

        //when no user is logged in
        $response = $this->json('POST', "/api/v1/contents/{$content->id}/views");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

        $this->assertDatabaseHas('views', [
            'viewable_id' => $content->id,
            'viewable_type' => 'content',
            'user_id' => null,
        ]);

        //when user is signed in
        $this->be($user);
        $response = $this->json('POST', "/api/v1/contents/{$content->id}/views");
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

        $this->assertDatabaseHas('views', [
            'viewable_id' => $content->id,
            'viewable_type' => 'content',
            'user_id' => $user->id,
        ]);
    }

    public function test_attach_media_to_content_fails_if_user_does_not_own_content()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $content = Models\Content::factory()
            ->audio()
            ->setTags([$tag1, $tag2])
            ->create();

        $this->be($user);
        $response = $this->json('POST', "/api/v1/contents/{$content->id}/attach-media");
        $response->assertStatus(400);
    }

    public function test_attach_media_to_content_works()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $content = Models\Content::factory()
            ->audio()
            ->for($user, 'owner')
            ->setTags([$tag1, $tag2])
            ->create();
        $asset1 = Models\Asset::factory()->create();
        $asset2 = Models\Asset::factory()->create();
        $this->be($user);
        $request = [
            'asset_ids' => [
                $asset1->id,
                $asset2->id,
            ]
        ];
        $response = $this->json('POST', "/api/v1/contents/{$content->id}/attach-media", $request);
        $response->assertStatus(200);
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $asset1->id,
            'purpose' => 'attached-media',
        ]);
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $asset2->id,
            'purpose' => 'attached-media',
        ]);
    }
}
