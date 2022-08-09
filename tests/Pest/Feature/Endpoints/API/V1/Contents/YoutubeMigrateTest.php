<?php

use App\Models;
use Tests\MockData;

test('video details are returned', function()
{   
    $user = Models\User::factory()->create();
    $this->be($user);

    $digiverse = Models\Collection::factory()
    ->for($user, 'owner')
    ->digiverse()
    ->create();

    // $request = 
    // [ 
    //     'url' => 'https://www.youtube.com/watch?v=BLmRXRBk5AQ',
    //     'digiverse_id' => $digiverse->id,
    //     'videoPrice' => 10,
    // ];

    $request = 
    [ 
        'url' => 'https://www.youtube.com/watch?v=BLmRXRBk5AQ'
    ];
    $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
});