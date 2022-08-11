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

    stub_request("https://youtube.googleapis.com/youtube/v3/videos?id={$videoId}&key={$secret}&part=snippet,contentDetails", [
        'items' =>
        [
            '0' => 
            [
                'snippet' =>
                [
                    'title' => 'A Youtube video title',
                    'description' => 'A Youtube video description',
                    'thumbnails' => [
                        'default' => [
                            'url' => 'https://i.ytimg.com/vi/I7MDn4etRuM/default.jpg',
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
                'price_in_dollars' => 10,
            ],
        ],      
        'digiverse_id' => $digiverse->id,
    ]);

    $response->assertStatus(200)->assertJson([
        'message' => 'Content has been created successfully',
    ]);

})->only();

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
    
    // $request = 
    // [ 
    //     'urls' => [
    //         [
    //             'url' => 'https://www.youtube.com/watch?v=I7MDn4etRuM',
    //             'price_in_dollars' => 10,
    //         ],
    //     ],      
    //     'digiverse_id' => $digiverse->id,
        
    // ];
    $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
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
})->skip();

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