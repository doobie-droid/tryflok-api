<?php

use App\Models;
use Tests\MockData;
use App\Mail\User\YoutubeMigrateMail;
use Illuminate\Support\Facades\Mail;

beforeEach(function()
{
        $this->user = Models\User::factory()->create();
        $this->be($this->user);

        $this->secret = config('services.google.youtube_api_key');
        

        $this->digiverse = Models\Collection::factory()
        ->for($this->user, 'owner')
        ->digiverse()
        ->create();

        $this->title = 'A Youtube video title';
        $this->description = 'A Youtube video description';
        $this->cover_url = 'https://i.ytimg.com/vi/I7MDn4etRuM/default.jpg';
        $this->price_in_dollars = 10;
});

test('content creation is successful with http://youtu.be/dQw4w9WgXcQ?feature=youtube_gdata_player', function()
{
    $videoId = 'dQw4w9WgXcQ';
    $cover_url = 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg';
    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$this->secret}&part=snippet,contentDetails", [
        'items' =>
        [
            '0' => 
            [   
                'id' => $videoId,
                'snippet' =>
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'tags' => [
                        '0' => 'tag1',
                        '1' => 'tag2'
                    ],
                    'thumbnails' => [
                        'maxres' => [
                            'url' => $cover_url,
                        ]
                    ]
                ]
            ]
        ]        
    ]);
    $response = $this->json('POST', '/api/v1/contents/youtube-migrate', [
        'urls' => [
            [   
                // 'url' => 'https://youtube.com?v=WEWE',
                // 'url' => 'https://www.youtube.com?v=WEWE',
                // 'url' => 'https://youtube.com/watch?WEWE',
                'url' => 'http://youtu.be/dQw4w9WgXcQ?feature=youtube_gdata_player',
                // 'url' => 'https://youtu.be/Sn2MIzIOHfo',
                // 'url' => 'http://youtube.com/?feature=channel&v=oTJRivZTMLs',
                // 'url' => 'http://www.youtube.com/user/dreamtheater#p/u/1/oTJRivZTMLs',
                // 'url' => 'http://youtube.com/watch?vi=dQw4w9WgXcQ&feature=youtube_gdata_player',
                // 'url' => 'https://www.youtube.com/v/0zM3nApSvMg?fs=1&amp;hl=en_US&amp;rel=0',
                // 'url' => 'http://youtu.be/-wtIMTCHWuI',
                // 'url' => 'https://www.youtube.com/watch?v=n0FHAOVpqGc&list=RDZCfrpi0tKHY&index=3',
                'price_in_dollars' => $this->price_in_dollars,
            ],
        ],      
        'digiverse_id' => $this->digiverse->id,
    ]);
    $response->assertStatus(200)->assertJson([
        'message' => 'Content has been created successfully',
    ]);

    $this->assertDatabaseHas('contents', [
        'title' => $this->title,
        'description' => $this->description,
        'user_id' => $this->user->id,
        'type' => 'video',
        'is_available' => 1,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
    ]);

    $content = Models\Content::where('title', $this->title)->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
         ]);
         $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

         //validate video asset was created
        $this->assertDatabaseHas('assets', [
            'url' => 'https://youtube.com/embed/'.$videoId,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'video',
            'mime_type' => 'video/mp4',
        ]);
        $this->assertDatabaseHas('assets', [
            'url' => $cover_url,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $content->assets()->first()->id,
            'purpose' => 'content-asset',
        ]);

        //  //validate tags was attached
        //  $tag1 = Models\Tag::where('name', 'tag1')->first();
        //  $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag1->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        // $tag2 = Models\Tag::where('name', 'tag2')->first();
        // $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag2->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $this->price_in_dollars,
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

test('content creation is successful with http://www.youtube.com/oembed?url=http%3A//www.youtube.com/watch?v%3D-wtIMTCHWuI&format=json
', function()
{
    $videoId = '-wtIMTCHWuI';
    $cover_url = 'https://i.ytimg.com/vi/-wtIMTCHWuI/0.jpg';
    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$this->secret}&part=snippet,contentDetails", [
        'items' =>
        [
            '0' => 
            [   'id' => $videoId,
                'snippet' =>
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'tags' => [
                        '0' => 'tag1',
                        '1' => 'tag2'
                    ],
                    'thumbnails' => [
                        'default' => [
                            'url' => $cover_url,
                        ]
                    ]
                ]
            ]
        ]        
    ]);
    $response = $this->json('POST', '/api/v1/contents/youtube-migrate', [
        'urls' => [
            [   
                'url' => 'http://www.youtube.com/oembed?url=http%3A//www.youtube.com/watch?v%3D-wtIMTCHWuI&format=json
                ',
                'price_in_dollars' => $this->price_in_dollars,
            ],
        ],      
        'digiverse_id' => $this->digiverse->id,
    ]);
    $response->assertStatus(200)->assertJson([
        'message' => 'Content has been created successfully',
    ]);

    $this->assertDatabaseHas('contents', [
        'title' => $this->title,
        'description' => $this->description,
        'user_id' => $this->user->id,
        'type' => 'video',
        'is_available' => 1,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
    ]);

    $content = Models\Content::where('title', $this->title)->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
         ]);
         $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

         //validate video asset was created
        $this->assertDatabaseHas('assets', [
            'url' => 'https://youtube.com/embed/'.$videoId,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'video',
            'mime_type' => 'video/mp4',
        ]);
        $this->assertDatabaseHas('assets', [
            'url' => $cover_url,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $content->assets()->first()->id,
            'purpose' => 'content-asset',
        ]);

        //  //validate tags was attached
        //  $tag1 = Models\Tag::where('name', 'tag1')->first();
        //  $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag1->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        // $tag2 = Models\Tag::where('name', 'tag2')->first();
        // $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag2->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $this->price_in_dollars,
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

test('content creation is successful with http://youtube.com/?feature=channel&v=oTJRivZTMLs', function()
{
    $videoId = 'oTJRivZTMLs';
    $cover_url = 'https://i.ytimg.com/vi/oTJRivZTMLs/0.jpg';
    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$this->secret}&part=snippet,contentDetails", [
        'items' =>
        [
            '0' => 
            [
                'id' => $videoId,
                'snippet' =>
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'tags' => [
                        '0' => 'tag1',
                        '1' => 'tag2'
                    ],
                    'thumbnails' => [
                        'default' => [
                            'url' => $cover_url,
                        ]
                    ]
                ]
            ]
        ]        
    ]);
    $response = $this->json('POST', '/api/v1/contents/youtube-migrate', [
        'urls' => [
            [   
                'url' => 'http://youtube.com/?feature=channel&v=oTJRivZTMLs',
                'price_in_dollars' => $this->price_in_dollars,
            ],
        ],      
        'digiverse_id' => $this->digiverse->id,
    ]);
    $response->assertStatus(200)->assertJson([
        'message' => 'Content has been created successfully',
    ]);

    $this->assertDatabaseHas('contents', [
        'title' => $this->title,
        'description' => $this->description,
        'user_id' => $this->user->id,
        'type' => 'video',
        'is_available' => 1,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
    ]);

    $content = Models\Content::where('title', $this->title)->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
         ]);
         $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

         //validate video asset was created
        $this->assertDatabaseHas('assets', [
            'url' => 'https://youtube.com/embed/'.$videoId,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'video',
            'mime_type' => 'video/mp4',
        ]);
        $this->assertDatabaseHas('assets', [
            'url' => $cover_url,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $content->assets()->first()->id,
            'purpose' => 'content-asset',
        ]);

        //  //validate tags was attached
        //  $tag1 = Models\Tag::where('name', 'tag1')->first();
        //  $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag1->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        // $tag2 = Models\Tag::where('name', 'tag2')->first();
        // $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag2->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $this->price_in_dollars,
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
test('content creation is successful with http://www.youtube.com/user/dreamtheater#p/u/1/oTJRivZTMLs', function()
{
    $videoId = 'oTJRivZTMLs';
    $cover_url = 'https://i.ytimg.com/vi/oTJRivZTMLs/0.jpg';
    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$this->secret}&part=snippet,contentDetails", [
        'items' =>
        [
            '0' => 
            [
                'id' => $videoId,
                'snippet' =>
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'tags' => [
                        '0' => 'tag1',
                        '1' => 'tag2'
                    ],
                    'thumbnails' => [
                        'default' => [
                            'url' => $cover_url,
                        ]
                    ]
                ]
            ]
        ]        
    ]);
    $response = $this->json('POST', '/api/v1/contents/youtube-migrate', [
        'urls' => [
            [   
                'url' => 'http://www.youtube.com/user/dreamtheater#p/u/1/oTJRivZTMLs',
                'price_in_dollars' => $this->price_in_dollars,
            ],
        ],      
        'digiverse_id' => $this->digiverse->id,
    ]);
    $response->assertStatus(200)->assertJson([
        'message' => 'Content has been created successfully',
    ]);

    $this->assertDatabaseHas('contents', [
        'title' => $this->title,
        'description' => $this->description,
        'user_id' => $this->user->id,
        'type' => 'video',
        'is_available' => 1,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
    ]);

    $content = Models\Content::where('title', $this->title)->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
         ]);
         $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

         //validate video asset was created
        $this->assertDatabaseHas('assets', [
            'url' => 'https://youtube.com/embed/'.$videoId,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'video',
            'mime_type' => 'video/mp4',
        ]);
        $this->assertDatabaseHas('assets', [
            'url' => $cover_url,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $content->assets()->first()->id,
            'purpose' => 'content-asset',
        ]);

         //validate tags was attached
        //  $tag1 = Models\Tag::where('name', 'tag1')->first();
        //  $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag1->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        // $tag2 = Models\Tag::where('name', 'tag2')->first();
        // $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag2->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $this->price_in_dollars,
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
                
test('content creation is successful with http://youtube.com/watch?vi=dQw4w9WgXcQ&feature=youtube_gdata_player', function()
{
    $videoId = 'dQw4w9WgXcQ';
    $cover_url = 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg';
    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$this->secret}&part=snippet,contentDetails", [
        'items' =>
        [
            '0' => 
            [
                'id' => $videoId,
                'snippet' =>
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'tags' => [
                        '0' => 'tag1',
                        '1' => 'tag2'
                    ],
                    'thumbnails' => [
                        'maxres' => [
                            'url' => $cover_url,
                        ]
                    ]
                ]
            ]
        ]        
    ]);
    $response = $this->json('POST', '/api/v1/contents/youtube-migrate', [
        'urls' => [
            [   
                'url' => 'http://youtube.com/watch?vi=dQw4w9WgXcQ&feature=youtube_gdata_player',
                'price_in_dollars' => $this->price_in_dollars,
            ],
        ],      
        'digiverse_id' => $this->digiverse->id,
    ]);
    $response->assertStatus(200)->assertJson([
        'message' => 'Content has been created successfully',
    ]);

    $this->assertDatabaseHas('contents', [
        'title' => $this->title,
        'description' => $this->description,
        'user_id' => $this->user->id,
        'type' => 'video',
        'is_available' => 1,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
    ]);

    $content = Models\Content::where('title', $this->title)->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
         ]);
         $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

         //validate video asset was created
        $this->assertDatabaseHas('assets', [
            'url' => 'https://youtube.com/embed/'.$videoId,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'video',
            'mime_type' => 'video/mp4',
        ]);
        $this->assertDatabaseHas('assets', [
            'url' => $cover_url,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $content->assets()->first()->id,
            'purpose' => 'content-asset',
        ]);

        //  //validate tags was attached
        //  $tag1 = Models\Tag::where('name', 'tag1')->first();
        //  $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag1->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        // $tag2 = Models\Tag::where('name', 'tag2')->first();
        // $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag2->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $this->price_in_dollars,
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

test('content creation is successful with https://youtube.com/embed/sUUGPYrh2ME', function()
{
    $videoId = 'sUUGPYrh2ME';
    $cover_url = 'https://i.ytimg.com/vi/sUUGPYrh2ME/maxresdefault.jpg';
    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$this->secret}&part=snippet,contentDetails", [
        'items' =>
        [
            '0' => 
            [
                'id' => $videoId,
                'snippet' =>
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'tags' => [
                        '0' => 'tag1',
                        '1' => 'tag2'
                    ],
                    'thumbnails' => [
                        'maxres' => [
                            'url' => $cover_url,
                        ]
                    ]
                ]
            ]
        ]        
    ]);
    $response = $this->json('POST', '/api/v1/contents/youtube-migrate', [
        'urls' => [
            [   
                'url' => 'https://youtube.com/embed/sUUGPYrh2ME',
                'price_in_dollars' => $this->price_in_dollars,
            ],
        ],      
        'digiverse_id' => $this->digiverse->id,
    ]);
    $response->assertStatus(200)->assertJson([
        'message' => 'Content has been created successfully',
    ]);

    $this->assertDatabaseHas('contents', [
        'title' => $this->title,
        'description' => $this->description,
        'user_id' => $this->user->id,
        'type' => 'video',
        'is_available' => 1,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
    ]);

    $content = Models\Content::where('title', $this->title)->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $this->digiverse->id,
            'content_id' => $content->id
         ]);
         $this->assertTrue($this->digiverse->contents()->where('contents.id', $content->id)->count() === 1);

         //validate video asset was created
        $this->assertDatabaseHas('assets', [
            'url' => 'https://youtube.com/embed/'.$videoId,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'video',
            'mime_type' => 'video/mp4',
        ]);
        $this->assertDatabaseHas('assets', [
            'url' => $cover_url,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertDatabaseHas('assetables', [
            'assetable_type' => 'content',
            'assetable_id' => $content->id,
            'asset_id' => $content->assets()->first()->id,
            'purpose' => 'content-asset',
        ]);

        //  //validate tags was attached
        //  $tag1 = Models\Tag::where('name', 'tag1')->first();
        //  $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag1->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        // $tag2 = Models\Tag::where('name', 'tag2')->first();
        // $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag2->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);

        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $this->price_in_dollars,
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
test('content is created with https://www.youtube.com/v/0zM3nApSvMg?fs=1&amp;hl=en_US&amp;rel=0', function()
{
    $videoId = '0zM3nApSvMg';
    $cover_url = 'https://i.ytimg.com/vi/0zM3nApSvMg/0.jpg';
    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$this->secret}&part=snippet,contentDetails", [
        'items' =>
        [
            '0' => 
            [
                'id' => $videoId,
                'snippet' =>
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'tags' => [
                        '0' => 'tag1',
                        '1' => 'tag2'
                    ],
                    'thumbnails' => [
                        'default' => [
                            'url' => $cover_url,
                        ]
                    ]
                ]
            ]
        ]        
    ]);
    $response = $this->json('POST', '/api/v1/contents/youtube-migrate', [
        'urls' => [
            [   
                'url' => 'https://www.youtube.com/v/0zM3nApSvMg?fs=1&amp;hl=en_US&amp;rel=0',
                'price_in_dollars' => $this->price_in_dollars,
            ],
        ],      
        'digiverse_id' => $this->digiverse->id,
    ]);
    $response->assertStatus(200);

    $this->assertDatabaseHas('contents', [
        'title' => $this->title,
        'description' => $this->description,
        'user_id' => $this->user->id,
        'type' => 'video',
        'is_available' => 1,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
    ]);
});

test('content is created with www.youtube.com/embed/sUUGPYrh2ME', function()
{
    $videoId = 'sUUGPYrh2ME';
    $cover_url = 'https://i.ytimg.com/vi/sUUGPYrh2ME/maxresdefault.jpg';
    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$this->secret}&part=snippet,contentDetails", [
        'items' =>
        [
            '0' => 
            [
                'id' => $videoId,
                'snippet' =>
                [
                    'title' => $this->title,
                    'description' => $this->description,
                    'tags' => [
                        '0' => 'tag1',
                        '1' => 'tag2'
                    ],
                    'thumbnails' => [
                        'maxres' => [
                            'url' => $cover_url,
                        ]
                    ]
                ]
            ]
        ]        
    ]);
    $response = $this->json('POST', '/api/v1/contents/youtube-migrate', [
        'urls' => [
            [   
                'url' => 'www.youtube.com/embed/sUUGPYrh2ME',
                'price_in_dollars' => $this->price_in_dollars,
            ],
        ],      
        'digiverse_id' => $this->digiverse->id,
    ]);
    $response->assertStatus(200);

    $this->assertDatabaseHas('contents', [
        'title' => $this->title,
        'description' => $this->description,
        'user_id' => $this->user->id,
        'type' => 'video',
        'is_available' => 1,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
    ]);
});

test('mail is sent for failed migrate', function()
{
    Mail::fake();

    $videoId = 'WEWE';
    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$this->secret}&part=snippet,contentDetails", [
        'items' =>
        [
        ]        
    ]);
    $response = $this->json('POST', '/api/v1/contents/youtube-migrate', [
        'urls' => [
            [   
                'url' => 'https://youtube.com/watch?WEWE',
                'price_in_dollars' => $this->price_in_dollars,
            ],
        ],      
        'digiverse_id' => $this->digiverse->id,
    ]);
    Mail::assertSent(YoutubeMigrateMail::class);
})->skip();
