<?php

use App\Models;
use Tests\MockData;

test('video details are returned', function()
{   
    $request = 
    [ 
        'url' => 'https://www.youtube.com/watch?v=BLmRXRBk5AQ'
    ];
    $response = $this->json('POST', "/api/v1/contents/youtube-migrate", $request);
});