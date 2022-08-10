<?php

use App\Models;
use Tests\MockData;

test('content gets created with correct data', function()
{   
    $user = Models\User::factory()->create();
    $this->be($user);

    $digiverse = Models\Collection::factory()
    ->for($user, 'owner')
    ->digiverse()
    ->create();

    $request = 
    [ 
        'urls' => [
            [
                'url' => 'https://www.youtube.com/watch?v=I7MDn4etRuM',
                'price_in_dollars' => 10,
            ],
        ],      
        'digiverse_id' => $digiverse->id,
        
    ];
    $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
    $response->assertStatus(200);

    $this->assertDatabaseHas('contents', [
        'title' => $request['title'],
        'description' => $request['description'],
        'user_id' => $user->id,
        'type' => 'video',
        'is_available' => 0,
        'approved_by_admin' => 1,
        'show_only_in_digiverses' => 1,
    ]);

    $this->assertDatabaseHas('assests', [
        'url'
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
        'asset_id' => $content->assets()->first()->id,
        'purpose' => 'content-asset',
    ]);
    
    $request = 
    [ 
        'urls' => [
            [
                'url' => 'https://www.youtube.com/watch?v=I7MDn4etRuM',
                'price_in_dollars' => 10,
            ],
        ],      
        'digiverse_id' => $digiverse->id,
        
    ];
    $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
    dd($response);
    $response->assertStatus(200);

})->only();

it('fails if youtube URL is inavlid', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);

        $digiverse = Models\Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();

        $request = 
        [ 
            'url' => 'https://www.youtube.com',
            'digiverse_id' => $digiverse->id,
            'price_in_dollars' => 10,
        ];
        $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
        $response->assertStatus(400)
        ->assertJson(MockData\YoutubeVideo::generateStandardUrlErrorResponse());

        $request = 
        [ 
            'url' => 'https://www.youtube.com/3kmlkslfkoi',
            'digiverse_id' => $digiverse->id,
            'price_in_dollars' => 10,
        ];
        $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
        $response->assertStatus(400)
        ->assertJson(MockData\YoutubeVideo::generateStandardUrlErrorResponse());

        $request = 
        [ 
            'url' => 'https://www.facebook.com',
            'digiverse_id' => $digiverse->id,
            'price_in_dollars' => 10,
        ];
        
        $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
        $response->assertStatus(400)
        ->assertJson(MockData\YoutubeVideo::generateStandardUrlErrorResponse());

        $request = 
        [ 
            'url' => 'https:/www.youtube.com',
            'digiverse_id' => $digiverse->id,
            'price_in_dollars' => 10,
        ];
        $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
        $response->assertStatus(400)
        ->assertJson(MockData\YoutubeVideo::generateStandardUrlErrorResponse());

        $request = 
        [ 
            'url' => 'https:/www.youtube.com/watch?v=BLmRXRBk5AQ',
            'digiverse_id' => $digiverse->id,
            'price_in_dollars' => 10,
        ];
        $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
        $response->assertStatus(400)
        ->assertJson(MockData\YoutubeVideo::generateStandardUrlErrorResponse());

        $request = 
        [ 
            'url' => 'https://www.youtube.comm/watch?v=BLmRXRBk5AQ',
            'digiverse_id' => $digiverse->id,
            'price_in_dollars' => 10,
        ];
        $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
        $response->assertStatus(400)
        ->assertJson(MockData\YoutubeVideo::generateStandardUrlErrorResponse());

        $request = 
        [ 
            'url' => 'https://www.youtube.com/watch?z=BLmRXRBk5AQ',
            'digiverse_id' => $digiverse->id,
            'price_in_dollars' => 10,
        ];
        $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
        $response->assertStatus(400)
        ->assertJson(MockData\YoutubeVideo::generateStandardUrlErrorResponse());
});

it('fails if digiverse is invalid', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);

        $digiverse = Models\Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();

        $request = 
        [ 
            'url' => 'https://www.youtube.com/watch?v=BLmRXRBk5AQ',
            'digiverse_id' => 'ee436e33',
            'price_in_dollars' => 10,
        ];
        $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
        $response->assertStatus(400);
});

it('fails if user does not own digiverse', function()
{
        $user = Models\User::factory()->create();
        $this->be($user);

        $digiverse = Models\Collection::factory()
        ->digiverse()
        ->create();

        $request = 
        [ 
            'url' => 'https://www.youtube.com/watch?v=BLmRXRBk5AQ',
            'digiverse_id' => $digiverse->id,
            'price_in_dollars' => 10,
        ];
        $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
        $response->assertStatus(400);
});

it('fails if price is invalid', function()
{
    $user = Models\User::factory()->create();
        $this->be($user);

        $digiverse = Models\Collection::factory()
        ->for($user, 'owner')
        ->digiverse()
        ->create();

        $request = 
        [ 
            'url' => 'https://www.youtube.com/watch?v=BLmRXRBk5AQ',
            'digiverse_id' => $digiverse->id,
            'price_in_dollars' => -1,
        ];
        $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
        $response->assertStatus(400);
});