<?php 

use App\Models;
use Tests\MockData;

beforeEach(function()
{
        $this->user = Models\User::factory()->create();
        $this->be($this->user);
        $this->digiverse = Models\Collection::factory()
        ->for($this->user, 'owner')
        ->digiverse()
        ->create();
        $this->tag1 = Models\Tag::factory()->create();
        $this->tag2 = Models\Tag::factory()->create();
        $this->coverAsset = Models\Asset::factory()->create();
        $this->audioAsset = Models\Asset::factory()->audio()->create();
        $this->pdfAsset = Models\Asset::factory()->pdf()->create();
        $this->videoAsset = Models\Asset::factory()->video()->create();
        $this->scheduled_date = now()->addDays(2);
        $this->contestant1 = Models\User::factory()->create();
        $this->contestant2 = Models\User::factory()->create();
});

test('content is not created with invalid inputs', function()
{
    $completeRequest = [
        'title' => 'A content',
        'description' => 'Content description',
        'type' => 'audio',
        'asset_id' => $this->audioAsset->id,
        'digiverse_id' => $this->digiverse->id,
        'is_available' => 0,
        'price' => [
            'amount' => 10,
        ],
        'tags' => [
            $this->tag1->id,
            $this->tag2->id,
        ],
        'cover' => [
            'asset_id' => $this->coverAsset->id,
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
    $request['cover']['asset_id'] = $this->audioAsset->id;
    $response = $this->json('POST', '/api/v1/contents', $request);
    $response->assertStatus(400);
    // when asset type does not match content type
    $request = $completeRequest;
    $request['asset_id'] = $this->pdfAsset->id;
    $response = $this->json('POST', '/api/v1/contents', $request);
    $response->assertStatus(400);
});

test('video content gets created', function()
{
    $request = [
        'title' => 'A content ' . date('YmdHis'),
        'description' => 'Content description',
        'type' => 'video',
        'asset_id' => $this->videoAsset->id,
        'digiverse_id' => $this->digiverse->id,
        'is_available' => 0,
        'price' => [
            'amount' => 10,
        ],
        'tags' => [
            $this->tag1->id,
            $this->tag2->id,
        ],
        'cover' => [
            'asset_id' => $this->coverAsset->id,
        ],
    ];
        $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $this->user->id,
            'type' => 'video',
            'is_available' => 0,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Models\Content::where('title', $request['title'])->first();
        // content is attached to collection
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);
        // validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag2->id)->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $this->coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate asset was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $this->videoAsset->id,
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
            'user_id' => $this->user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
});

test('audio content gets created', function()
{
    $request = [
        'title' => 'A content ' . date('YmdHis'),
        'description' => 'Content description',
        'type' => 'audio',
        'asset_id' => $this->audioAsset->id,
        'digiverse_id' => $this->digiverse->id,
        'is_available' => 0,
        'price' => [
            'amount' => 10,
        ],
        'tags' => [
            $this->tag1->id,
            $this->tag2->id,
        ],
        'cover' => [
            'asset_id' => $this->coverAsset->id,
        ],
    ];

    $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $this->user->id,
            'type' => 'audio',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Models\Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
         ]);
        $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag2->id)->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $this->coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate asset was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $this->audioAsset->id,
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
            'user_id' => $this->user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
});

test('pdf content gets created', function()
{
    $request = [
        'title' => 'A content ' . date('YmdHis'),
        'description' => 'Content description',
        'type' => 'pdf',
        'asset_id' => $this->pdfAsset->id,
        'digiverse_id' => $this->digiverse->id,
        'is_available' => 0,
        'price' => [
            'amount' => 10,
        ],
        'tags' => [
            $this->tag1->id,
            $this->tag2->id,
        ],
        'cover' => [
            'asset_id' => $this->coverAsset->id,
        ],
    ];

    $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $this->user->id,
            'type' => 'pdf',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Models\Content::where('title', $request['title'])->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
         ]);
        $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag2->id)->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $this->coverAsset->id,
            'purpose' => 'cover',
        ]);
        $this->assertTrue($content->cover()->count() === 1);

        //validate asset was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $this->pdfAsset->id,
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
            'user_id' => $this->user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
});

test('newsletter content gets created', function()
{
    $newsletter_pe = '[
        [
          {
            "name": "a",
            "range": {
              "start": 0,
              "end": 5
            }
          }
        ]
    ]';
    $request = [
        'title' => 'A content ' . date('YmdHis'),
        'description' => 'a escription',
        'article' => "<p><img style=\"display: block; margin-left: auto; margin-right: auto;\" title=\"Tiny Logo\" src=\"https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png\" alt=\"TinyMCE Logo\" width=\"128\" height=\"128\" /></p> <h2 style=\"text-align: center;\">Welcome to the TinyMCE editor demo!</h2> <h2>Got questions or need help?</h2> <ul> <li>Our <a href=\"https://www.tiny.cloud/docs/\">documentation</a> is a great resource for learning how to configure TinyMCE.</li> <li>Have a specific question? Try the <a href=\"https://stackoverflow.com/questions/tagged/tinymce\" target=\"_blank\" rel=\"noopener\"><code>tinymce</code> tag at Stack Overflow</a>.</li> <li>We also offer enterprise grade support as part of <a href=\"https://www.tiny.cloud/pricing\">TinyMCE premium plans</a>.</li> </ul> <h2>A simple table to play with</h2> <table style=\"border-collapse: collapse; width: 100%;\" border=\"1\"> <thead> <tr> <th>Product</th> <th>Cost</th> <th>Really?</th> </tr> </thead> <tbody> <tr> <td>TinyMCE</td> <td>Free</td> <td>YES!</td> </tr> <tr> <td>Plupload</td> <td>Free</td> <td>YES!</td> </tr> </tbody> </table> <h2>Found a bug?</h2> <p> If you think you have found a bug please create an issue on the <a href=\"https://github.com/tinymce/tinymce/issues\">GitHub repo</a> to report it to the developers. </p> <h2>Finally ...</h2> <p> Don't forget to check out our other product <a href=\"http://www.plupload.com\" target=\"_blank\">Plupload</a>, your ultimate upload solution featuring HTML5 upload support. </p> <p> Thanks for supporting TinyMCE! We hope it helps you and your users create great content.<br>All the best from the TinyMCE team. </p>",
        'type' => 'newsletter',
        'digiverse_id' => $this->digiverse->id,
        'is_available' => 0,
        'price' => [
            'amount' => 0,
        ],
        'tags' => [
            $this->tag1->id,
            $this->tag2->id,
        ],
        'cover' => [
            'asset_id' => $this->coverAsset->id,
        ],
        'newsletter_position_elements' => $newsletter_pe,

    ];

    $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateStandardCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $this->user->id,
            'type' => 'newsletter',
            'is_available' => 0,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
        ]);
        $content = Models\Content::where('title', $request['title'])->first();
        $decoded_content_newsletter_pe = json_decode($content->newsletter_position_elements);
        $decoded_newsletter_pe = json_decode($newsletter_pe);
        $this->assertTrue($decoded_newsletter_pe[0][0]->name == $decoded_content_newsletter_pe[0][0]->name);

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
         ]);
        $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag2->id)->count() === 1);

        //validate cover was attached
        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $this->coverAsset->id,
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
            'asset_id' => $content->assets()->first()->id,
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
            'user_id' => $this->user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
});

test('live audio content gets created', function()
{
    $request = [
        'title' => 'A content ' . date('YmdHis'),
        'description' => 'Content description',
        'type' => 'live-audio',
        'digiverse_id' => $this->digiverse->id,
        'is_available' => 0,
        'price' => [
            'amount' => 0,
        ],
        'tags' => [
            $this->tag1->id,
            $this->tag2->id,
        ],
        'cover' => [
            'asset_id' => $this->coverAsset->id,
        ],
        'scheduled_date' => $this->scheduled_date->format('Y-m-d H:i:s'),
    ];

    $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateLiveContentCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $this->user->id,
            'type' => 'live-audio',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
            'scheduled_date' => $this->scheduled_date->format('Y-m-d H:i:s'),
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

        $this->assertDatabaseHas('content_live_hosts', [
            'content_id' => $content->id,
            'user_id' => $this->user->id,
            'designation' => 'host',
        ]);

        $this->assertDatabaseHas('content_live_broadcasters', [
            'content_id' => $content->id,
            'user_id' => $this->user->id,
            'video_stream_status' => 'inactive',
            'audio_stream_status' => 'inactive',
        ]);

        // content is attached to collection
        $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag2->id)->count() === 1);

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
            'user_id' => $this->user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
});

test('live video content gets created', function()
{
    $request = [
        'title' => 'A content ' . date('YmdHis'),
        'description' => 'Content description',
        'type' => 'live-video',
        'digiverse_id' => $this->digiverse->id,
        'is_available' => 0,
        'price' => [
            'amount' => 0,
        ],
        'tags' => [
            $this->tag1->id,
            $this->tag2->id,
        ],
        'cover' => [
            'asset_id' => $this->coverAsset->id,
        ],
        'scheduled_date' => $this->scheduled_date->format('Y-m-d H:i:s'),
    ];

    $response = $this->json('POST', '/api/v1/contents', $request);
        $response->assertStatus(200)
        ->assertJsonStructure(MockData\Content::generateLiveContentCreateResponse());

        $this->assertDatabaseHas('contents', [
            'title' => $request['title'],
            'description' => $request['description'],
            'user_id' => $this->user->id,
            'type' => 'live-video',
            'is_available' => 1,
            'approved_by_admin' => 1,
            'show_only_in_digiverses' => 1,
            'scheduled_date' => $this->scheduled_date->format('Y-m-d H:i:s'),
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
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
        ]);
        $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

        //validate tags was attached
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag1->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag1->id)->count() === 1);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $this->tag2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $this->tag2->id)->count() === 1);

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
            'user_id' => $this->user->id,
            'share' => 100,
        ]);
        $this->assertTrue($content->benefactors()->count() === 1);
});

test('challenge does not get created with invalid values', function()
{
    $completeRequest = [
        'title' => 'A content ' . date('YmdHis'),
        'description' => 'Content description',
        'type' => 'live-video',
        'digiverse_id' => $this->digiverse->id,
        'is_available' => 0,
        'price' => [
            'amount' => 0,
        ],
        'tags' => [
            $this->tag1->id,
            $this->tag2->id,
        ],
        'cover' => [
            'asset_id' => $this->coverAsset->id,
        ],
        'scheduled_date' => $this->scheduled_date->format('Y-m-d H:i:s'),
        'is_challenge' => 1,
        'pot_size' => 1000,
        'minimum_contribution' => 10,
        'moderator_share' => 10,
        'winner_share' => 60,
        'loser_share' => 30,
        'contestants' => [
            $this->contestant1->id,
            $this->contestant2->id,
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
        $this->contestant1->id,
        'sdsdsdsd',
    ];
    $response = $this->json('POST', '/api/v1/contents', $request);
    $response->assertStatus(400);
    //when user tries to add themself
    $request = $completeRequest;
    $request['contestants'] = [
        $this->contestant1->id,
        $this->user->id,
    ];
    $response = $this->json('POST', '/api/v1/contents', $request);
    $response->assertStatus(400);
    // when same user is passed twice
    $request = $completeRequest;
    $request['contestants'] = [
        $this->contestant1->id,
        $this->contestant1->id,
    ];
    $response = $this->json('POST', '/api/v1/contents', $request);
    $response->assertStatus(400);
});

test('challenge content gets created', function()
{
    $request = [
        'title' => 'A content ' . date('YmdHis'),
        'description' => 'Content description',
        'type' => 'live-video',
        'digiverse_id' => $this->digiverse->id,
        'is_available' => 0,
        'price' => [
            'amount' => 0,
        ],
        'tags' => [
            $this->tag1->id,
            $this->tag2->id,
        ],
        'cover' => [
            'asset_id' => $this->coverAsset->id,
        ],
        'scheduled_date' => $this->scheduled_date->format('Y-m-d H:i:s'),
        'is_challenge' => 1,
        'pot_size' => 1000,
        'minimum_contribution' => 10,
        'moderator_share' => 10,
        'winner_share' => 60,
        'loser_share' => 30,
        'contestants' => [
            $this->contestant1->id,
            $this->contestant2->id,
        ]
    ];
    $response = $this->json('POST', '/api/v1/contents', $request);
    $response->assertStatus(200);
    //->assertJsonStructure(MockData\Content::generateChallengeContentCreateResponse());

    $this->assertDatabaseHas('contents', [
        'title' => $request['title'],
        'description' => $request['description'],
        'user_id' => $this->user->id,
        'type' => 'live-video',
        'is_available' => 1,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
        'scheduled_date' => $this->scheduled_date->format('Y-m-d H:i:s'),
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
        'user_id' => $this->contestant1->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('content_challenge_contestants', [
        'content_id' => $content->id,
        'user_id' => $this->contestant2->id,
        'status' => 'pending',
    ]);
    // contestants were made braodcasters
    $this->assertDatabaseHas('content_live_broadcasters', [
        'content_id' => $content->id,
        'user_id' => $this->contestant1->id,
        'video_stream_status' => 'inactive',
        'audio_stream_status' => 'inactive',
    ]);
    $this->assertDatabaseHas('content_live_broadcasters', [
        'content_id' => $content->id,
        'user_id' => $this->contestant2->id,
        'video_stream_status' => 'inactive',
        'audio_stream_status' => 'inactive',
    ]);
    // content is attached to collection
    $this->assertDatabaseHas('collection_content', [
        'collection_id' => $this->digiverse->id,
        'content_id' => $content->id
    ]);
    $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

    //validate tags was attached
    $this->assertDatabaseHas('taggables', [
        'tag_id' => $this->tag1->id,
        'taggable_type' => 'content',
        'taggable_id' => $content->id,
    ]);
    $this->assertTrue($content->tags()->where('tags.id', $this->tag1->id)->count() === 1);
    $this->assertDatabaseHas('taggables', [
        'tag_id' => $this->tag2->id,
        'taggable_type' => 'content',
        'taggable_id' => $content->id,
    ]);
    $this->assertTrue($content->tags()->where('tags.id', $this->tag2->id)->count() === 1);

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
        'user_id' => $this->user->id,
        'share' => 100,
    ]);
    $this->assertTrue($content->benefactors()->count() === 1);

    $this->assertDatabaseHas('notifications', [
        'recipient_id' => $this->contestant1->id,
        'notifier_id' => $this->user->id,
        'notificable_type' => 'content',
        'notificable_id' => $content->id,
        'message' => "You have been added as a contestant to the {$content->title} challenge. You can choose to accept or decline",
    ]);

    $this->assertDatabaseHas('notifications', [
        'recipient_id' => $this->contestant2->id,
        'notifier_id' => $this->user->id,
        'notificable_type' => 'content',
        'notificable_id' => $content->id,
        'message' => "You have been added as a contestant to the {$content->title} challenge. You can choose to accept or decline",
    ]);
});
