<?php

namespace Tests\Feature\Controllers\API\V1\ContentController;

use App\Constants;
use App\Models;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\MockData;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_content_is_not_created_with_invalid_inputs()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $coverAsset = Models\Asset::factory()->create();
        $audioAsset = Models\Asset::factory()->audio()->create();
        $pdfAsset = Models\Asset::factory()->pdf()->create();
        $completeRequest = [
            'title' => 'A content',
            'description' => 'Content description',
            'type' => 'audio',
            'asset_id' => $audioAsset->id,
            'digiverse_id' => $digiverse->id,
            'is_available' => 0,
            'price' => [
                'amount' => 10,
            ],
            'tags' => [
                $tag1->id,
                $tag2->id,
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
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Models\Asset::factory()->create();
        $videoAsset = Models\Asset::factory()->video()->create();
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'video',
            'asset_id' => $videoAsset->id,
            'digiverse_id' => $digiverse->id,
            'is_available' => 0,
            'price' => [
                'amount' => 10,
            ],
            'tags' => [
                $tag1->id,
                $tag2->id
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'video',
            'is_available' => 0,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Models\Content::where('title', $request['title'])->first();
        // content is attached to collection
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);
        // validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag2->id)->count() === 1);

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
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Models\Asset::factory()->create();
        $audioAsset = Models\Asset::factory()->audio()->create();
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'audio',
            'asset_id' => $audioAsset->id,
            'digiverse_id' => $digiverse->id,
            'is_available' => 0,
            'price' => [
                'amount' => 10,
            ],
            'tags' => [
                $tag1->id,
                $tag2->id,
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'audio',
            'is_available' => 0,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Models\Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
         ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag2->id)->count() === 1);

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
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Models\Asset::factory()->create();
        $pdfAsset = Models\Asset::factory()->pdf()->create();
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'pdf',
            'asset_id' => $pdfAsset->id,
            'digiverse_id' => $digiverse->id,
            'is_available' => 0,
            'price' => [
                'amount' => 10,
            ],
            'tags' => [
                $tag1->id,
                $tag2->id,
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'pdf',
            'is_available' => 0,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Models\Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
         ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag2->id)->count() === 1);

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
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $coverAsset = Models\Asset::factory()->create();
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'a escription',
            'article' => "<p><img style=\"display: block; margin-left: auto; margin-right: auto;\" title=\"Tiny Logo\" src=\"https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png\" alt=\"TinyMCE Logo\" width=\"128\" height=\"128\" /></p> <h2 style=\"text-align: center;\">Welcome to the TinyMCE editor demo!</h2> <h2>Got questions or need help?</h2> <ul> <li>Our <a href=\"https://www.tiny.cloud/docs/\">documentation</a> is a great resource for learning how to configure TinyMCE.</li> <li>Have a specific question? Try the <a href=\"https://stackoverflow.com/questions/tagged/tinymce\" target=\"_blank\" rel=\"noopener\"><code>tinymce</code> tag at Stack Overflow</a>.</li> <li>We also offer enterprise grade support as part of <a href=\"https://www.tiny.cloud/pricing\">TinyMCE premium plans</a>.</li> </ul> <h2>A simple table to play with</h2> <table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"> <thead> <tr> <th>Product</th> <th>Cost</th> <th>Really?</th> </tr> </thead> <tbody> <tr> <td>TinyMCE</td> <td>Free</td> <td>YES!</td> </tr> <tr> <td>Plupload</td> <td>Free</td> <td>YES!</td> </tr> </tbody> </table> <h2>Found a bug?</h2> <p> If you think you have found a bug please create an issue on the <a href=\"https://github.com/tinymce/tinymce/issues\">GitHub repo</a> to report it to the developers. </p> <h2>Finally ...</h2> <p> Don't forget to check out our other product <a href=\"http://www.plupload.com\" target=\"_blank\">Plupload</a>, your ultimate upload solution featuring HTML5 upload support. </p> <p> Thanks for supporting TinyMCE! We hope it helps you and your users create great content.<br>All the best from the TinyMCE team. </p>",
            'type' => 'newsletter',
            'digiverse_id' => $digiverse->id,
            'is_available' => 0,
            'price' => [
                'amount' => 0,
            ],
            'tags' => [
                $tag1->id,
                $tag2->id,
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'newsletter',
            'is_available' => 0,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Models\Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
         ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag2->id)->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate article asset was created
        $this->assertDatabaseHas('assets', [
            'asset_type' => 'text',
            'mime_type' => 'text/html',
        ]);
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' =>  $content->assets()->first()->id,
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

    public function test_live_audio_content_gets_created()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $scheduled_date = now()->addDays(2);
        $coverAsset = Models\Asset::factory()->create();
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'live-audio',
            'digiverse_id' => $digiverse->id,
            'is_available' => 0,
            'price' => [
                'amount' => 0,
            ],
            'tags' => [
                $tag1->id,
                $tag2->id,
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateLiveContentCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'live-audio',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
            'live_status' => 'inactive',
        ]);
        $content = Models\Content::where('title', $request['title'])->first();

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
            'tag_id' => $tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag2->id)->count() === 1);

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
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $scheduled_date = now()->addDays(2);
        $coverAsset = Models\Asset::factory()->create();
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'live-video',
            'digiverse_id' => $digiverse->id,
            'is_available' => 0,
            'price' => [
                'amount' => 0,
            ],
            'tags' => [
                $tag1->id,
                $tag2->id,
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateLiveContentCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'live-video',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
            'live_status' => 'inactive',
        ]);
        $content = Models\Content::where('title', $request['title'])->first();

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
            'tag_id' => $tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag2->id)->count() === 1);

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

    public function test_challenge_does_not_get_created_with_invalida_values()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $contestant1 = Models\User::factory()->create();
        $contestant1->assignRole(Constants\Roles::USER);
        $contestant2 = Models\User::factory()->create();
        $contestant2->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $scheduled_date = now()->addDays(2);
        $coverAsset = Models\Asset::factory()->create();
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $completeRequest = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'live-video',
            'digiverse_id' => $digiverse->id,
            'is_available' => 0,
            'price' => [
                'amount' => 0,
            ],
            'tags' => [
                $tag1->id,
                $tag2->id,
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
            'is_challenge' => 1,
            'pot_size' => 1000,
            'minimum_contribution' => 10,
            'moderator_share' => 10,
            'winner_share' => 60,
            'loser_share' => 30,
            'contestants' => [
                $contestant1->id,
                $contestant2->id,
            ]
        ];

        //when contesnt type is not live video
        $request = $completeRequest;
        $request['type'] = 'live-audio';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when is_challenge is invalid
        $request = $completeRequest;
        $request['is_challenge'] = 'true';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when pot size is invalid
        $request = $completeRequest;
        $request['pot_size'] = null;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['pot_size'] = 'string';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['pot_size'] = 123232.43;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when minimum contribution is invalid
        $request = $completeRequest;
        $request['minimum_contribution'] = null;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['minimum_contribution'] = 'string';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['minimum_contribution'] = 3454545.54;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when moderator share is invalid
        $request = $completeRequest;
        $request['moderator_share'] = null;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['moderator_share'] = 'string';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['moderator_share'] = 15;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['moderator_share'] = -1;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when winner share is invalid
        $request = $completeRequest;
        $request['winner_share'] = null;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['winner_share'] = 'string';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['winner_share'] = 44;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['winner_share'] = 40;
        $request['loser_share'] = 50;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when loser share is omitted
        $request = $completeRequest;
        $request['loser_share'] = null;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['loser_share'] = 'string';
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        $request = $completeRequest;
        $request['loser_share'] = 51;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        // when sums don't add up
        $request = $completeRequest;
        $request['moderator_share'] = 5;
        $request['winner_share'] = 50;
        $request['loser_share'] = 20;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when contestants are omitted
        $request = $completeRequest;
        $request['contestants'] = null;
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when contestants are invalide
        $request = $completeRequest;
        $request['contestants'] = [
            $contestant1->id,
            'sdsdsdsd',
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
        //when user tries to add themself
        $request = $completeRequest;
        $request['contestants'] = [
            $contestant1->id,
            $user->id,
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(400);
    }

    public function test_challenge_content_gets_created()
    {
        $user = Models\User::factory()->create();
        $user->assignRole(Constants\Roles::USER);
        $contestant1 = Models\User::factory()->create();
        $contestant1->assignRole(Constants\Roles::USER);
        $contestant2 = Models\User::factory()->create();
        $contestant2->assignRole(Constants\Roles::USER);
        $this->be($user);
        $digiverse = Models\Collection::factory()
        ->digiverse()
        ->for($user, 'owner')
        ->create();
        $scheduled_date = now()->addDays(2);
        $coverAsset = Models\Asset::factory()->create();
        $tag1 = Models\Tag::factory()->create();
        $tag2 = Models\Tag::factory()->create();
        $request = [
            'title' => 'A content ' . date('YmdHis'),
            'description' => 'Content description',
            'type' => 'live-video',
            'digiverse_id' => $digiverse->id,
            'is_available' => 0,
            'price' => [
                'amount' => 0,
            ],
            'tags' => [
                $tag1->id,
                $tag2->id,
            ],
            'cover' => [
                'asset_id' => $coverAsset->id,
            ],
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
            'is_challenge' => 1,
            'pot_size' => 1000,
            'minimum_contribution' => 10,
            'moderator_share' => 10,
            'winner_share' => 60,
            'loser_share' => 30,
            'contestants' => [
                $contestant1->id,
                $contestant2->id,
            ]
        ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateLiveContentCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $user->id,
            'type' => 'live-video',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
            'scheduled_date' => $scheduled_date->format('Y-m-d H:i:s'),
            'live_status' => 'inactive',
            'is_challenge' => 1,
        ]);
        $content = Models\Content::where('title', $request['title'])->first();

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

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'pot_size',
            'value' => $request['pot_size'],
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'minimum_contribution',
            'value' => $request['minimum_contribution'],
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'moderator_share',
            'value' => $request['moderator_share'],
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'winner_share',
            'value' => $request['winner_share'],
        ]);

        $this->assertDatabaseHas('metas', [
            'metaable_type' => 'content',
            'metaable_id' => $content->id,
            'key' => 'loser_share',
            'value' => $request['loser_share'],
        ]);

        // contestants were added
        $this->assertDatabaseHas('content_challenge_contestants', [
            'content_id' => $content->id,
            'user_id' => $contestant1->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('content_challenge_contestants', [
            'content_id' => $content->id,
            'user_id' => $contestant2->id,
            'status' => 'pending',
        ]);

        // content is attached to collection
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag2->id)->count() === 1);

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

    /**
     * public function test_create_newletter_issue_works() {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->hasAttached(
            Content::factory()->state([
                'type' => 'newsletter',
                'user_id' => $user->id,
            ]),
            [
                'id' => Str::uuid(),
            ]
        )
        ->create();

        $newsletter = $digiverse->contents()->where('type', 'newsletter')->first();
        $request = [
            'title' => 'First Issue ' . date('YmdHis'),
            'description' => "<p><img style=\"display: block; margin-left: auto; margin-right: auto;\" title=\"Tiny Logo\" src=\"https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png\" alt=\"TinyMCE Logo\" width=\"128\" height=\"128\" /></p> <h2 style=\"text-align: center;\">Welcome to the TinyMCE editor demo!</h2> <h2>Got questions or need help?</h2> <ul> <li>Our <a href=\"https://www.tiny.cloud/docs/\">documentation</a> is a great resource for learning how to configure TinyMCE.</li> <li>Have a specific question? Try the <a href=\"https://stackoverflow.com/questions/tagged/tinymce\" target=\"_blank\" rel=\"noopener\"><code>tinymce</code> tag at Stack Overflow</a>.</li> <li>We also offer enterprise grade support as part of <a href=\"https://www.tiny.cloud/pricing\">TinyMCE premium plans</a>.</li> </ul> <h2>A simple table to play with</h2> <table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"> <thead> <tr> <th>Product</th> <th>Cost</th> <th>Really?</th> </tr> </thead> <tbody> <tr> <td>TinyMCE</td> <td>Free</td> <td>YES!</td> </tr> <tr> <td>Plupload</td> <td>Free</td> <td>YES!</td> </tr> </tbody> </table> <h2>Found a bug?</h2> <p> If you think you have found a bug please create an issue on the <a href=\"https://github.com/tinymce/tinymce/issues\">GitHub repo</a> to report it to the developers. </p> <h2>Finally ...</h2> <p> Don't forget to check out our other product <a href=\"http://www.plupload.com\" target=\"_blank\">Plupload</a>, your ultimate upload solution featuring HTML5 upload support. </p> <p> Thanks for supporting TinyMCE! We hope it helps you and your users create great content.<br>All the best from the TinyMCE team. </p>",
        ];
        $response = $this->json('POST', "/api/v1/contents/{$newsletter->id}/issues", $request);
        $response->assertStatus(200)->assertJsonStructure(ContentMock::STANDARD_ISSUE_RESPONSE);
        $this->assertDatabaseHas('content_issues', [
            'title' => $request['title'],
            'description' => $request['description'],
            'content_id' => $newsletter->id,
            'is_available' => 0,
            'views' => 0,
        ]);
    }

    public function test_update_newletter_issue_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $newsletter = Content::factory()->state([
            'type' => 'newsletter',
            'user_id' => $user->id,
        ])
        ->hasAttached(
            $digiverse,
            [
                'id' => Str::uuid(),
            ]
        )
        ->create();

        $issue = ContentIssue::factory()
        ->state([
            'title' => 'First Issue ' . date('YmdHis'),
            'is_available' => 0,
            'description' => "<p><img style=\"display: block; margin-left: auto; margin-right: auto;\" title=\"Tiny Logo\" src=\"https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png\" alt=\"TinyMCE Logo\" width=\"128\" height=\"128\" /></p> <h2 style=\"text-align: center;\">Welcome to the TinyMCE editor demo!</h2> <h2>Got questions or need help?</h2> <ul> <li>Our <a href=\"https://www.tiny.cloud/docs/\">documentation</a> is a great resource for learning how to configure TinyMCE.</li> <li>Have a specific question? Try the <a href=\"https://stackoverflow.com/questions/tagged/tinymce\" target=\"_blank\" rel=\"noopener\"><code>tinymce</code> tag at Stack Overflow</a>.</li> <li>We also offer enterprise grade support as part of <a href=\"https://www.tiny.cloud/pricing\">TinyMCE premium plans</a>.</li> </ul> <h2>A simple table to play with</h2> <table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"> <thead> <tr> <th>Product</th> <th>Cost</th> <th>Really?</th> </tr> </thead> <tbody> <tr> <td>TinyMCE</td> <td>Free</td> <td>YES!</td> </tr> <tr> <td>Plupload</td> <td>Free</td> <td>YES!</td> </tr> </tbody> </table> <h2>Found a bug?</h2> <p> If you think you have found a bug please create an issue on the <a href=\"https://github.com/tinymce/tinymce/issues\">GitHub repo</a> to report it to the developers. </p> <h2>Finally ...</h2> <p> Don't forget to check out our other product <a href=\"http://www.plupload.com\" target=\"_blank\">Plupload</a>, your ultimate upload solution featuring HTML5 upload support. </p> <p> Thanks for supporting TinyMCE! We hope it helps you and your users create great content.<br>All the best from the TinyMCE team. </p>"
        ])
        ->for($newsletter, 'content')
        ->create();

        $request = [
            'issue_id' => $issue->id,
            'title' => 'Update First Issue ' . date('YmdHis'),
            'description' => '<p>Hello World</p>',
        ];
        $response = $this->json('PUT', "/api/v1/contents/{$newsletter->id}/issues", $request);
        $response->assertStatus(200)->assertJsonStructure(ContentMock::STANDARD_ISSUE_RESPONSE);
        $this->assertDatabaseHas('content_issues', [
            'id' => $issue->id,
            'title' => $request['title'],
            'description' => $request['description'],
            'content_id' => $newsletter->id,
            'is_available' => 0,
            'views' => 0,
        ]);
    }

    public function test_publish_newletter_issue_works()
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::USER);
        $this->be($user);
        $digiverse = Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();
        $newsletter = Content::factory()->state([
            'type' => 'newsletter',
            'user_id' => $user->id,
        ])
        ->hasAttached(
            $digiverse,
            [
                'id' => Str::uuid(),
            ]
        )
        ->hasAttached(
            User::factory()->count(1),
            [
                'id' => Str::uuid(),
            ],
            'subscribers'
        )
        ->hasAttached(
            User::factory()->count(1),
            [
                'id' => Str::uuid(),
            ],
            'subscribers'
        )
        ->hasAttached(
            User::factory()->count(1),
            [
                'id' => Str::uuid(),
            ],
            'subscribers'
        )
        ->hasAttached(
            User::factory()->count(1),
            [
                'id' => Str::uuid(),
            ],
            'subscribers'
        )
        ->create();

        $issue = ContentIssue::factory()
        ->state([
            'title' => 'First Issue ' . date('YmdHis'),
            'description' => '<p>Hello World</p>',
            'is_available' => 0,
        ])
        ->for($newsletter, 'content')
        ->create();

        $this->assertTrue($newsletter->notifiers()->count() === 0);
        $this->assertDatabaseHas('content_issues', [
            'id' => $issue->id,
            'content_id' => $newsletter->id,
            'is_available' => 0,
        ]);
        $request = [
            'issue_id' => $issue->id,
        ];
        $response = $this->json('PATCH', "/api/v1/contents/{$newsletter->id}/issues", $request);
        $response->assertStatus(200)->assertJsonStructure(ContentMock::STANDARD_ISSUE_RESPONSE);
        $this->assertDatabaseHas('content_issues', [
            'id' => $issue->id,
            'content_id' => $newsletter->id,
            'is_available' => 1,
        ]);
        $this->assertTrue($newsletter->notifiers()->count() === 4);
    }
     */
}
