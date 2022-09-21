<?php

use App\Constants\Roles;
use App\Models;
use Tests\MockData;

test('add to collection works', function() 
{
    $user = Models\User::factory()->create();
    $this->be($user);
    $collection = Models\Collection::factory()->create();
    $content = Models\Content::factory()->create();
    $request = [
        'contents' => [
            [
            'id' => $content->id,
            'action' => 'add',
            ],
        ],
    ];
    $response = $this->json('PATCH', "/api/v1/collections/{$collection->id}/contents", $request);
    $response->assertStatus(200)
    ->assertJsonStructure([
        'data' => [
            'collection' => [
                'id',
                'title',
                'description',
                'user_id', // owner of collection
                'type', // book, series, channel, digiverse
                'is_available',
                'approved_by_admin',
                'show_only_in_collections',
                'views',
            ]
        ]
    ]
    );
});