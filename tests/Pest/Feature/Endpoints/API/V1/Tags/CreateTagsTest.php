<?php

use App\Models;
use Tests\MockData;

test('create tags works', function()
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $request = [
        'tags' => [
            [
                'name' => 'Lifestyle',
            ],
            [
                'name' => 'Money',
            ],
            [
                'name' => 'Yolo',
            ],
        ]
        ];
        $response = $this->json('POST', '/api/v1/tags', $request);
        $response->assertStatus(200);
        $tags = Models\Tag::where('user_id', $user->id)->get();
        $this->assertEquals($tags->count(), 3);
});