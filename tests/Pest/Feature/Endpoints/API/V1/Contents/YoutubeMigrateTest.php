<?php

use App\Models;
use Tests\MockData;

test('video details are returned', function()
{   
    $user = Models\User::factory()->create();
    $this->be($user);

    $request = 
    [ 
        'url' => 'https://www.youtube.com/watch?v=BLmRXRBk5AQ'
    ];
    $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
});