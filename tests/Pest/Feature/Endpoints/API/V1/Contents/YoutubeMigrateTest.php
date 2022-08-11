<?php

use App\Models;
use Tests\MockData;

test('content creation is successful with correct data', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);

    $secret = config('services.google.youtube_api_key');
    $videoId = 'I7MDn4etRuM';

    $digiverse = Models\Collection::factory()
    ->for($user, 'owner')
    ->digiverse()
    ->create();

    $title = 'A Youtube video title';
    $description = 'A Youtube video description';
    $url = 'https://i.ytimg.com/vi/I7MDn4etRuM/default.jpg';
    $tag_1 = Models\Tag::factory()->create();
    $tag_2 = Models\Tag::factory()->create();
    $price_in_dollars = 10;

    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$secret}&part=snippet,contentDetails", [
        'items' =>
        [
            '0' => 
            [
                'snippet' =>
                [
                    'title' => $title,
                    'description' => $description,
                    'tags' => [
                        '0' => $tag_1->id,
                        '1' => $tag_2->id,
                    ],
                    'thumbnails' => [
                        'default' => [
                            'url' => $url,
                        ]
                    ]
                ]
            ]
        ]        
    ]);
    $response = $this->json('POST', '/api/v1/contents/youtube-migrate', [
        'urls' => [
            [
                'url' => 'https://www.youtube.com/watch?v=I7MDn4etRuM',
                'price_in_dollars' => $price_in_dollars,
            ],
        ],      
        'digiverse_id' => $digiverse->id,
    ]);

    $response->assertStatus(200)->assertJson([
        'message' => 'Content has been created successfully',
    ]);

    $this->assertDatabaseHas('contents', [
        'title' => $title,
        'description' => $description,
        'user_id' => $user->id,
        'type' => 'video',
        'is_available' => 0,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
    ]);

    $content = Models\Content::where('title', $title)->first();

         // content is attached to collection
         $this->assertDatabaseHas('collection_content', [
            'collection_id' => $digiverse->id,
            'content_id' => $content->id
         ]);
         $this->assertTrue($digiverse->contents()->where('contents.id', $content->id)->count() === 1);

         //validate video asset was created
        $this->assertDatabaseHas('assets', [
            'url' => 'https://youtube.com/embed/'.$videoId,
            'storage_provider' => 'youtube',
            'storage_provider_id' => $videoId,
            'asset_type' => 'video',
            'mime_type' => 'video/mp4',
        ]);
        $this->assertDatabaseHas('assets', [
            'url' => $url,
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
        //  $this->assertDatabaseHas('taggables', [
        //     'tag_id' => $tag_1->id,
        //     'taggable_type' => 'content',
        //     'taggable_id' => $content->id,
        // ]);
        // $this->assertTrue($content->tags()->where('tags.id', $tag_1->id)->count() === 1);

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag_2->id,
            'taggable_type' => 'content',
            'taggable_id' => $content->id,
        ]);
        $this->assertTrue($content->tags()->where('tags.id', $tag_2->id)->count() === 1);


        //validate price was created
        $this->assertDatabaseHas('prices', [
            'priceable_type' => 'content',
            'priceable_id' => $content->id,
            'amount' => $price_in_dollars,
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
});