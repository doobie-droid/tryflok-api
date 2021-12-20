<?php

namespace Tests\Feature\Content;

use App\Constants\Roles;
use App\Models\Asset;
use App\Models\Collection;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData\Content as ContentMock;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_content_is_not_created_with_invalid_inputs()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $coverAsset = Asset::factory()->create();
        $audioAsset = Asset::factory()->audio()->create();
        $pdfAsset = Asset::factory()->pdf()->create();
        $completeRequest = [
            'title' => 'A content',
            'description' => 'Content description',
            'type' => 'audio',
            'asset_id' => $audioAsset->id,
            'digiverse_id' => $digiverse->id,
            'is_available' => 1,
            'price' => [
                'amount' => 10,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff',
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        // when no digiverse id is supplied
        $request = $completeRequest;
        $request['digiverse_id'] = '0a14760d-1d41-45aa-a820-87d6dc35f7fz';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when invalid digiverse id is supplied
        $request = $completeRequest;
        $request['digiverse_id'] = '';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when title is ommited
        $request = $completeRequest;
        $request['title'] = '';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when scheduled_date is in the past
        $request = $completeRequest;
        $scheduled_date = now()->subDays(2);
        $request['scheduled_date'] = $scheduled_date->format('Y-m-d H:i:s');
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when type is omitted
        $request = $completeRequest;
        $request['type'] = '';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when type is not valid
        $request = $completeRequest;
        $request['type'] = 'apolo';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when cover is omitted
        $request = $completeRequest;
        $request['cover']['asset_id'] = '';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when wrong asset is ommitted for audio
        $request = $completeRequest;
        $request['cover']['asset_id'] = '';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when wrong asset is used for cover
        $request = $completeRequest;
        $request['cover']['asset_id'] = $audioAsset->id;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when asset type does not match content type
        $request = $completeRequest;
        $request['asset_id'] = $pdfAsset->id;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
    }

    public function test_video_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Asset::factory()->create();
        $videoAsset = Asset::factory()->video()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'video',
            'asset_id' => $videoAsset->id,
            'digiverse_id' => $digiverse->id,
            'is_available' => 1,
            'price' => [
                'amount' => 10,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff',
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)->assertJsonStructure(ContentMock::CONTENT_WITH_NO_ASSET_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'video',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Content::where('title', $request['title'])->first();
        // content is attached to collection
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);
        // validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate asset was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $videoAsset->id,
            'purpose' => 'content-asset',
        ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => 'one-off',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }

    public function test_audio_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Asset::factory()->create();
        $audioAsset = Asset::factory()->audio()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'audio',
            'asset_id' => $audioAsset->id,
            'digiverse_id' => $digiverse->id,
            'is_available' => 1,
            'price' => [
                'amount' => 10,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff',
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(ContentMock::CONTENT_WITH_NO_ASSET_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'audio',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
         ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate asset was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $audioAsset->id,
            'purpose' => 'content-asset',
        ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => 'one-off',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }

    public function test_pdf_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Asset::factory()->create();
        $pdfAsset = Asset::factory()->pdf()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'pdf',
            'asset_id' => $pdfAsset->id,
            'digiverse_id' => $digiverse->id,
            'is_available' => 1,
            'price' => [
                'amount' => 10,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff',
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(ContentMock::CONTENT_WITH_NO_ASSET_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'pdf',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
         ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate asset was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $pdfAsset->id,
            'purpose' => 'content-asset',
        ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => 'one-off',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }

    public function test_newsletter_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Asset::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => "<p><img style=\"display: block; margin-left: auto; margin-right: auto;\" title=\"Tiny Logo\" src=\"https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png\" alt=\"TinyMCE Logo\" width=\"128\" height=\"128\" /></p> <h2 style=\"text-align: center;\">Welcome to the TinyMCE editor demo!</h2> <h2>Got questions or need help?</h2> <ul> <li>Our <a href=\"https://www.tiny.cloud/docs/\">documentation</a> is a great resource for learning how to configure TinyMCE.</li> <li>Have a specific question? Try the <a href=\"https://stackoverflow.com/questions/tagged/tinymce\" target=\"_blank\" rel=\"noopener\"><code>tinymce</code> tag at Stack Overflow</a>.</li> <li>We also offer enterprise grade support as part of <a href=\"https://www.tiny.cloud/pricing\">TinyMCE premium plans</a>.</li> </ul> <h2>A simple table to play with</h2> <table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"> <thead> <tr> <th>Product</th> <th>Cost</th> <th>Really?</th> </tr> </thead> <tbody> <tr> <td>TinyMCE</td> <td>Free</td> <td>YES!</td> </tr> <tr> <td>Plupload</td> <td>Free</td> <td>YES!</td> </tr> </tbody> </table> <h2>Found a bug?</h2> <p> If you think you have found a bug please create an issue on the <a href=\"https://github.com/tinymce/tinymce/issues\">GitHub repo</a> to report it to the developers. </p> <h2>Finally ...</h2> <p> Don't forget to check out our other product <a href=\"http://www.plupload.com\" target=\"_blank\">Plupload</a>, your ultimate upload solution featuring HTML5 upload support. </p> <p> Thanks for supporting TinyMCE! We hope it helps you and your users create great content.<br>All the best from the TinyMCE team. </p>",
            'type' => 'newsletter',
            'digiverse_id' => $digiverse->id,
            'is_available' => 1,
            'price' => [
                'amount' => 0,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff',
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(ContentMock::CONTENT_WITH_NO_ASSET_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'newsletter',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
         ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => 'one-off',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }

    public function test_live_audio_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $scheduled_date = now()->addDays(2);
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'live-audio',
            'digiverse_id' => $digiverse->id,
            'is_available' => 1,
            'price' => [
                'amount' => 0,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff',
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(ContentMock::CONTENT_WITH_NO_COVER_AND_ASSET_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'live-audio',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
            'live_status' => 'inactive',
        ]);
        $content = Content::where('title', $request['title'])->first();

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'channel_name',
            'value' => "{$content->id}-" . date('Ymd'),
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'join_count',
            'value' => 0,
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'rtc_token',
            'value' => '',
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'rtm_token',
            'value' => '',
        ]);

        // content is attached to collection
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => 'one-off',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }

    public function test_live_video_content_gets_created()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $scheduled_date = now()->addDays(2);
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'live-video',
            'digiverse_id' => $digiverse->id,
            'is_available' => 1,
            'price' => [
                'amount' => 0,
            ],
            'tags' => [
                '0e14760d-1d41-45aa-a820-87d6dc35f7ff',
                '120566de-0361-4d66-b458-321d4ede62a9'
            ],
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(ContentMock::CONTENT_WITH_NO_COVER_AND_ASSET_RESPONSE);

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'live-video',
            'is_available' => 1,
            'approved_by_admin' => 0,
            'show_only_in_digiverses' => 1,
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
            'live_status' => 'inactive',
        ]);
        $content = Content::where('title', $request['title'])->first();

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'channel_name',
            'value' => "{$content->id}-" . date('Ymd'),
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'join_count',
            'value' => 0,
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'rtc_token',
            'value' => '',
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'rtm_token',
            'value' => '',
        ]);

        // content is attached to collection
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][0],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][0])->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $request['tags'][1],
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $request['tags'][1])->count() === 1);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $request['price']['amount'],
            'interval' => 'one-off',
            'interval_amount' => 1,
            'currency' => 'USD',
        ]);
        $this->assertTrue($content->prices()->count() === 1);

        //validate benefactor was created
        $this->assertDatabaseHas('benefactors', [
            'benefactable_type' => 'content',
            'benefactable_id' => $content->id,
            'user_id' => $user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
    }
}
